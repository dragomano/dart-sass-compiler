<?php

declare(strict_types=1);

namespace DartSass\Handlers;

use DartSass\Compiler;
use DartSass\Exceptions\CompilationException;
use DartSass\Modules\SassList;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\IdentifierNode;
use Throwable;

use function array_key_exists;
use function array_key_first;
use function count;
use function is_array;
use function md5;
use function preg_replace;
use function serialize;
use function str_replace;
use function trim;

class MixinHandler
{
    private array $mixins = [];

    private array $contentStack = [];

    private ?string $currentContent = null;

    private static array $mixinCache = [];

    private const CACHE_LIMIT = 100;

    public function define(string $name, array $args, array $body): void
    {
        $this->mixins[$name] = [
            'args' => $args,
            'body' => $body,
        ];
    }

    private static function generateCacheKey(string $name, array $args, ?array $content): string
    {
        try {
            $serializedArgs = serialize($args);
            $serializedContent = serialize($content);

            return $name . '|' . md5($serializedArgs . $serializedContent);
        } catch (Throwable) {
            // If serialization fails, don't cache
            return '';
        }
    }

    public function include(
        string    $name,
        array     $args,
        ?array    $content = null,
        ?Compiler $parentCompiler = null,
        string    $parentSelector = '',
        int       $nestingLevel = 0
    ): string {
        if (! isset($this->mixins[$name])) {
            throw new CompilationException("Undefined mixin: $name");
        }

        $cacheKey = self::generateCacheKey($name, $args, $content);
        if ($cacheKey !== '' && isset(self::$mixinCache[$cacheKey])) {
            return self::$mixinCache[$cacheKey];
        }

        $mixin = $this->mixins[$name];

        // Always use parent compiler if available to preserve context
        if ($parentCompiler !== null) {
            $compiler = $parentCompiler;
        } else {
            // Fallback for when no parent compiler is provided
            $compiler = new Compiler(['style' => 'expanded']);
        }

        $compiler->variableHandler->enterScope();

        $argIndex = 0;

        // Check if we have a SassList that needs to be unpacked
        if (count($args) === 1 && $args[0] instanceof SassList) {
            $sassList = $args[0];
            $arguments = $sassList->value;
        } elseif (count($args) === 1 && is_array($args[0]) && ! isset($args[0]['value'])) {
            $arguments = $args[0];
        } else {
            $arguments = $args;
        }

        foreach ($mixin['args'] as $argName => $defaultValue) {
            if (array_key_exists($argIndex, $arguments)) {
                $value = $arguments[$argIndex];
            } else {
                if ($defaultValue instanceof IdentifierNode) {
                    if ($defaultValue->properties['value'] === 'null') {
                        $value = null;
                    } else {
                        $value = $defaultValue;
                    }
                } else {
                    $value = $defaultValue;
                }
            }

            $compiler->variableHandler->define($argName, $value);
            $argIndex++;
        }

        $compiledContent = null;

        if ($content) {
            $compiledContent = '';
            if (! empty($parentSelector) && count($content) > 0 && is_array($content[0])) {
                $declarationCss = '';
                foreach ($content as $item) {
                    if (is_array($item)) {
                        $result = $compiler->compileDeclarations([$item], $nestingLevel + 2);
                        $declarationCss .= $result;
                    }
                }

                $compiledContent = $compiler->formatRule($parentSelector, $declarationCss, $nestingLevel);
                $compiledContent = preg_replace('/^}$/m', '  }', $compiledContent);
            } else {
                foreach ($content as $item) {
                    if (is_array($item)) {
                        $result = $compiler->compileDeclarations([$item], 1);
                        $compiledContent .= $result;
                    } elseif ($item instanceof AstNode) {
                        if (! empty($parentSelector)) {
                            $result = $compiler->compileAst([$item], $parentSelector);
                        } else {
                            $result = $compiler->compileAst([$item]);
                        }

                        $compiledContent .= $result;
                    }
                }
            }

            $this->currentContent = $compiledContent;
        }

        $css = '';
        foreach ($mixin['body'] as $item) {
            if (is_array($item)) {
                $result = $compiler->compileDeclarations([$item], 1);
                $css .= $result;
            } elseif ($item instanceof AstNode) {
                $result = $compiler->compileAst([$item], $parentSelector);
                $css .= $result;
            }
        }

        $compiledContent ??= '';
        if (trim($compiledContent) === '') {
            $css = str_replace('@content', '', $css);
        } else {
            $css = preg_replace('/@content\s*;?\s*{[^}]*}\s*/', $compiledContent, $css);
        }

        $this->currentContent = null;
        $compiler->variableHandler->exitScope();

        if ($cacheKey !== '') {
            self::$mixinCache[$cacheKey] = $css;
            if (count(self::$mixinCache) > self::CACHE_LIMIT) {
                $firstKey = array_key_first(self::$mixinCache);
                unset(self::$mixinCache[$firstKey]);
            }
        }

        return $css;
    }

    public function getMixins(): array
    {
        return [
            'mixins'         => $this->mixins,
            'contentStack'   => $this->contentStack,
            'currentContent' => $this->currentContent,
        ];
    }

    public function setMixins(array $state): void
    {
        $this->mixins         = $state['mixins'] ?? [];
        $this->contentStack   = $state['contentStack'] ?? [];
        $this->currentContent = $state['currentContent'] ?? null;
    }

    public function removeMixin(string $name): void
    {
        unset($this->mixins[$name]);
    }
}
