<?php

declare(strict_types=1);

namespace DartSass\Handlers;

use DartSass\Compilers\CompilerContext;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\IdentifierNode;
use DartSass\Values\SassList;
use DartSass\Values\SassMap;
use Throwable;

use function array_key_exists;
use function array_key_first;
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
    private ?string $currentContent = null;

    private static array $mixinCache = [];

    private const CACHE_LIMIT = 100;

    public function __construct(private readonly CompilerContext $context) {}

    public function define(string $name, array $args, array $body, bool $global = false): void
    {
        $this->context->environment->getCurrentScope()->setMixin($name, $args, $body, $global);
    }

    public function hasMixin(string $name): bool
    {
        return $this->context->environment->getCurrentScope()->hasMixin($name);
    }

    public function hasContent(): bool
    {
        return $this->currentContent !== null;
    }

    public function include(
        string $name,
        array $args,
        ?array $content = null,
        string $parentSelector = '',
        int $nestingLevel = 0
    ): string {
        $mixin = $this->context->environment->getCurrentScope()->getMixin($name);

        $cacheKey = self::generateCacheKey($name, $args, $content);
        if ($cacheKey !== '' && isset(self::$mixinCache[$cacheKey])) {
            return self::$mixinCache[$cacheKey];
        }

        $this->context->environment->enterScope();

        try {
            $arguments = $this->normalizeArguments($args);
            $this->bindArguments($mixin['args'], $arguments);

            $compiledContent = $this->compileContent($content, $parentSelector, $nestingLevel);

            $css = $this->compileMixinBody($mixin['body'], $parentSelector);
            $css = $this->injectContent($css, $compiledContent);

            self::storeCacheResult($cacheKey, $css);

            return $css;
        } finally {
            $this->context->environment->exitScope();
        }
    }

    public function getMixin(string $name): ?array
    {
        return $this->context->environment->getCurrentScope()->getMixin($name);
    }

    public function removeMixin(string $name): void {}

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

            $this->context->variableHandler->define($argName, $value);

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

        $this->context->variableHandler->define($varName, $value);
    }

    private function resolveArgumentValue(
        string $argName,
        int $argIndex,
        array $arguments,
        mixed $defaultValue,
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
                $declarationCss .= $this->context->engine->compileDeclarations(
                    [$item],
                    nestingLevel: $nestingLevel + 2
                );
            }
        }

        $compiledContent = $this->context->engine->formatRule($declarationCss, $parentSelector, $nestingLevel);

        return preg_replace('/^}$/m', '  }', $compiledContent);
    }

    private function compileContentDeclarations(array $content): string
    {
        $compiledContent = '';

        foreach ($content as $item) {
            if (is_array($item)) {
                $compiledContent .= $this->context->engine->compileDeclarations([$item], nestingLevel: 1);
            }
        }

        return $compiledContent;
    }

    private function compileMixinBody(array $body, string $parentSelector): string
    {
        $css = '';

        foreach ($body as $item) {
            if (is_array($item)) {
                $css .= $this->context->engine->compileDeclarations([$item], nestingLevel: 1);
            } elseif ($item instanceof AstNode) {
                $css .= $this->context->engine->compileAst([$item], $parentSelector);
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
