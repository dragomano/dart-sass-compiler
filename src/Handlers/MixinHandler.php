<?php

declare(strict_types=1);

namespace DartSass\Handlers;

use DartSass\Compilers\CompilerEngineInterface;
use DartSass\Exceptions\CompilationException;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\IdentifierNode;
use DartSass\Values\SassList;
use DartSass\Values\SassMap;
use Throwable;

use function array_key_exists;
use function array_key_first;
use function array_pop;
use function count;
use function in_array;
use function is_array;
use function is_int;
use function md5;
use function preg_replace;
use function serialize;
use function str_ends_with;
use function str_replace;
use function str_starts_with;
use function substr;
use function trim;

class MixinHandler
{
    private array $mixins = [];

    private array $scopes = [];

    private array $contentStack = [];

    private ?string $currentContent = null;

    private CompilerEngineInterface $compilerEngine;

    private static array $mixinCache = [];

    private const CACHE_LIMIT = 100;

    public function setCompilerEngine(CompilerEngineInterface $engine): void
    {
        $this->compilerEngine = $engine;
    }

    public function define(string $name, array $args, array $body, bool $global = false): void
    {
        $mixinData = [
            'args' => $args,
            'body' => $body,
        ];

        if ($global || empty($this->scopes)) {
            $this->mixins[$name] = $mixinData;
        } else {
            $this->scopes[array_key_last($this->scopes)][$name] = $mixinData;
        }
    }

    public function enterScope(): void
    {
        $this->scopes[] = [];
    }

    public function exitScope(): void
    {
        if (! empty($this->scopes)) {
            array_pop($this->scopes);
        }
    }

    public function include(
        string $name,
        array $args,
        ?array $content = null,
        string $parentSelector = '',
        int $nestingLevel = 0
    ): string {
        $mixin = $this->findMixin($name);

        if (! isset($mixin)) {
            throw new CompilationException("Undefined mixin: $name");
        }

        $cacheKey = self::generateCacheKey($name, $args, $content);
        if ($cacheKey !== '' && isset(self::$mixinCache[$cacheKey])) {
            return self::$mixinCache[$cacheKey];
        }

        $compilerEngine = $this->compilerEngine;
        $compilerEngine->getContext()->variableHandler->enterScope();

        $arguments = $this->normalizeArguments($args);
        $this->bindArguments($mixin['args'], $arguments);

        $compiledContent = $this->compileContent($content, $parentSelector, $nestingLevel);

        $css = $this->compileMixinBody($mixin['body'], $parentSelector);
        $css = $this->injectContent($css, $compiledContent);

        $compilerEngine->getContext()->variableHandler->exitScope();

        self::storeCacheResult($cacheKey, $css);

        return $css;
    }

    public function setMixins(array $state): void
    {
        $this->mixins         = $state['mixins'] ?? [];
        $this->contentStack   = $state['contentStack'] ?? [];
        $this->currentContent = $state['currentContent'] ?? null;
    }

    public function getMixins(): array
    {
        return [
            'mixins'         => $this->mixins,
            'contentStack'   => $this->contentStack,
            'currentContent' => $this->currentContent,
        ];
    }

    public function removeMixin(string $name): void
    {
        unset($this->mixins[$name]);
    }

    public function hasMixin(string $name): bool
    {
        return $this->findMixin($name) !== null;
    }

    private function findMixin(string $name): ?array
    {
        for ($i = count($this->scopes) - 1; $i >= 0; $i--) {
            if (isset($this->scopes[$i][$name])) {
                return $this->scopes[$i][$name];
            }
        }

        return $this->mixins[$name] ?? null;
    }

    private static function generateCacheKey(string $name, array $args, ?array $content): string
    {
        try {
            $serializedArgs    = serialize($args);
            $serializedContent = serialize($content);

            return $name . '|' . md5($serializedArgs . $serializedContent);
        } catch (Throwable) {
            return '';
        }
    }

    private function normalizeArguments(array $args): array
    {
        if (count($args) !== 1 || ! isset($args[0])) {
            return $args;
        }

        return match (true) {
            $args[0] instanceof SassList => $args[0]->value,
            is_array($args[0]) && ! isset($args[0]['value']) => $args[0],
            default => $args
        };
    }

    private function bindArguments(array $mixinArgs, array $arguments): void
    {
        $argIndex = 0;
        $usedKeys = [];

        foreach ($mixinArgs as $argName => $defaultValue) {
            if (str_ends_with($argName, '...')) {
                $this->bindSpreadArgument($argName, $arguments, $usedKeys);

                break;
            }

            $value = $this->resolveArgumentValue($argName, $argIndex, $arguments, $defaultValue, $usedKeys);

            $this->compilerEngine->getContext()->variableHandler->define($argName, $value);

            $argIndex++;
        }
    }

    private function bindSpreadArgument(string $argName, array $arguments, array $usedKeys): void
    {
        $varName = substr($argName, 0, -3);

        $remainingKeywords   = [];
        $remainingPositional = [];

        foreach ($arguments as $key => $val) {
            if (in_array($key, $usedKeys, true)) {
                continue;
            }

            if (is_int($key)) {
                $remainingPositional[] = $val;
            } else {
                $keyStr = $key;

                if (str_starts_with($keyStr, '$')) {
                    $keyStr = substr($keyStr, 1);
                }

                $remainingKeywords[$keyStr] = $val;
            }
        }

        $value = empty($remainingKeywords) ? new SassList($remainingPositional) : new SassMap($remainingKeywords);

        $this->compilerEngine->getContext()->variableHandler->define($varName, $value);
    }

    private function resolveArgumentValue(
        string $argName,
        int $argIndex,
        array $arguments,
        $defaultValue,
        array &$usedKeys
    ) {
        if (array_key_exists($argName, $arguments)) {
            $usedKeys[] = $argName;

            return $arguments[$argName];
        }

        if (array_key_exists($argIndex, $arguments)) {
            $usedKeys[] = $argIndex;

            return $arguments[$argIndex];
        }

        if ($defaultValue instanceof IdentifierNode && $defaultValue->value === 'null') {
            return null;
        }

        return $defaultValue;
    }

    private function compileContent(?array $content, string $parentSelector, int $nestingLevel): ?string
    {
        if (! $content) {
            return null;
        }

        if (! empty($parentSelector) && count($content) > 0 && is_array($content[0])) {
            return $this->compileContentWithSelector($content, $parentSelector, $nestingLevel);
        }

        return $this->compileContentDeclarations($content);
    }

    private function compileContentWithSelector(array $content, string $parentSelector, int $nestingLevel): string
    {
        $declarationCss = '';

        foreach ($content as $item) {
            if (is_array($item)) {
                $declarationCss .= $this->compilerEngine->compileDeclarations([$item], nestingLevel: $nestingLevel + 2);
            }
        }

        $compiledContent = $this->compilerEngine->formatRule($declarationCss, $parentSelector, $nestingLevel);

        return preg_replace('/^}$/m', '  }', $compiledContent);
    }

    private function compileContentDeclarations(array $content): string
    {
        $compiledContent = '';

        foreach ($content as $item) {
            if (is_array($item)) {
                $compiledContent .= $this->compilerEngine->compileDeclarations([$item], nestingLevel: 1);
            }
        }

        return $compiledContent;
    }

    private function compileMixinBody(array $body, string $parentSelector): string
    {
        $css = '';

        foreach ($body as $item) {
            if (is_array($item)) {
                $css .= $this->compilerEngine->compileDeclarations([$item], nestingLevel: 1);
            } elseif ($item instanceof AstNode) {
                $css .= $this->compilerEngine->compileAst([$item], $parentSelector);
            }
        }

        return $css;
    }

    private function injectContent(string $css, ?string $compiledContent): string
    {
        $compiledContent ??= '';

        if (trim($compiledContent) === '') {
            return str_replace('@content', '', $css);
        }

        return preg_replace('/@content\s*;?\s*{[^}]*}\s*/', $compiledContent, $css);
    }

    private static function storeCacheResult(string $cacheKey, string $css): void
    {
        if ($cacheKey === '') {
            return;
        }

        self::$mixinCache[$cacheKey] = $css;

        if (count(self::$mixinCache) > self::CACHE_LIMIT) {
            $firstKey = array_key_first(self::$mixinCache);
            unset(self::$mixinCache[$firstKey]);
        }
    }
}
