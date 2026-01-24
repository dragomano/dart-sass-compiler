<?php

declare(strict_types=1);

namespace DartSass\Modules;

use Closure;
use DartSass\Compilers\CompilerContext;
use DartSass\Exceptions\CompilationException;
use DartSass\Handlers\ModuleRegistry;
use DartSass\Loaders\FileLoader;
use DartSass\Loaders\HttpLoader;
use DartSass\Parsers\Nodes\HexColorNode;
use DartSass\Parsers\Nodes\VariableDeclarationNode;
use DartSass\Parsers\Syntax;
use DartSass\Utils\StringFormatter;
use DartSass\Values\CalcValue;
use DartSass\Values\SassColor;
use DartSass\Values\SassFunction;
use DartSass\Values\SassList;
use DartSass\Values\SassMap;
use DartSass\Values\SassMixin;
use DartSass\Values\SassNumber;
use DartSass\Values\SassUserFunction;

use function addslashes;
use function array_map;
use function array_shift;
use function call_user_func_array;
use function dirname;
use function explode;
use function filter_var;
use function implode;
use function in_array;
use function is_array;
use function is_bool;
use function is_callable;
use function is_string;
use function preg_match;
use function reset;
use function str_ends_with;
use function str_starts_with;
use function strtolower;
use function trim;

use const FILTER_VALIDATE_URL;

class MetaModule extends AbstractModule
{
    public function __construct(
        private readonly ModuleRegistry  $moduleRegistry,
        private readonly CompilerContext $context,
        private readonly ?Closure        $evaluator = null
    ) {}

    public function apply(array $args): string
    {
        $this->validateArgs($args, 1, 'apply', true);

        $mixin = array_shift($args);

        if ($mixin instanceof SassMixin) {
            return $mixin->apply($args);
        }

        if (is_string($mixin)) {
            $mixins = $this->context->mixinHandler->getMixins()['mixins'];

            if (! isset($mixins[$mixin])) {
                throw new CompilationException("Unknown mixin: $mixin");
            }

            return $this->context->mixinHandler->include($mixin, $args);
        }

        throw new CompilationException('apply() first argument must be a SassMixin, mixin name or callable');
    }

    public function loadCss(array $args): string
    {
        [$url] = $this->validateArgs($args, 1, 'load-css');

        if (! is_string($url)) {
            throw new CompilationException('load-css() argument must be a string');
        }

        $isRelative = ! preg_match('#^(https?://|/)#i', $url);

        $engine = $this->context->engine;

        if ($isRelative) {
            $loadPaths = [];

            if (isset($this->context->options['sourceFile'])) {
                $sourceFile = $this->context->options['sourceFile'];
                $currentDir = dirname($sourceFile);

                if ($currentDir !== '.' && $currentDir !== '/') {
                    $loadPaths[] = $currentDir;
                }
            }

            $loader  = new FileLoader($loadPaths);
        } else {
            if (! filter_var($url, FILTER_VALIDATE_URL)) {
                throw new CompilationException('load-css() argument must be a valid URL');
            }

            $loader  = new HttpLoader();
        }

        $content = $loader->load($url);

        if (str_ends_with($url, '.scss') || str_ends_with($url, '.sass')) {
            return $engine->compileString($content, Syntax::fromPath($url));
        }

        return $content;
    }

    public function acceptsContent(array $args): bool
    {
        [$mixin] = $this->validateArgs($args, 1, 'accepts-content');

        if ($mixin instanceof SassMixin) {
            return $mixin->acceptsContent();
        }

        if (is_string($mixin)) {
            $mixinObj = new SassMixin($this->context->mixinHandler, $mixin);

            return $mixinObj->acceptsContent();
        }

        if (! is_callable($mixin) || ! isset($mixin->mixinName)) {
            return false;
        }

        $mixinObj = new SassMixin($this->context->mixinHandler, $mixin->mixinName);

        return $mixinObj->acceptsContent();
    }

    public function calcArgs(array $args): SassList
    {
        [$calculation] = $this->validateArgs($args, 1, 'calc-args');

        if ($calculation instanceof CalcValue) {
            $calcArgs = $calculation->getArgs();
            $operator = $calculation->getOperator();

            return new SassList([$calcArgs[0], $operator, $calcArgs[1]], 'space');
        }

        if (is_string($calculation) && preg_match('/^([a-zA-Z-]+)\((.+)\)$/', $calculation, $matches)) {
            $func  = strtolower($matches[1]);
            $inner = trim($matches[2]);

            if ($func === 'calc') {
                if (preg_match('/^(.+)\s*([+\-*\/])\s*(.+)$/', $inner, $parts)) {
                    return new SassList([trim($parts[1]), $parts[2], trim($parts[3])], 'space');
                }
            } else {
                // For functions like clamp, min, max, etc. with comma-separated args
                $args = array_map(trim(...), explode(',', $inner));

                return new SassList($args, 'comma');
            }
        }

        throw new CompilationException('calc-args() argument must be a calculation');
    }

    public function calcName(array $args): string
    {
        [$calculation] = $this->validateArgs($args, 1, 'calc-name');

        if ($calculation instanceof CalcValue || is_string($calculation)) {
            return StringFormatter::forceQuoteString('calc');
        }

        throw new CompilationException('calc-name() argument must be a calculation');
    }

    public function call(array $args)
    {
        $this->validateArgs($args, 1, 'call', true);

        $function = array_shift($args);

        if (is_callable($function)) {
            return call_user_func_array($function, $args);
        }

        if (is_string($function)) {
            $handler = $this->moduleRegistry->getHandler($function);
            if ($handler !== null) {
                return $handler->handle($function, $args);
            }

            try {
                return $this->context->functionHandler->call($function, $args);
            } catch (CompilationException) {
            }

            throw new CompilationException("Unknown function: $function");
        }

        throw new CompilationException('call() first argument must be a function name or callable');
    }

    public function contentExists(array $args): bool
    {
        $this->validateArgs($args, 0, 'content-exists');

        $mixins = $this->context->mixinHandler->getMixins();

        return $mixins['currentContent'] !== null;
    }

    public function featureExists(array $args): bool
    {
        [$feature] = $this->validateArgs($args, 1, 'feature-exists');

        if (! is_string($feature)) {
            throw new CompilationException('feature-exists() argument must be a string');
        }

        $supportedFeatures = [
            'at-error',
            'custom-property',
            'extend-selector-pseudoclass',
            'global-variable-shadowing',
            'units-level-3',
        ];

        return in_array($feature, $supportedFeatures, true);
    }

    public function functionExists(array $args): bool
    {
        $this->validateArgs($args, 1, 'function-exists', true);

        $functionName = $args[0] ?? null;
        $moduleName   = $args[1] ?? null;

        if ($functionName === null) {
            return false;
        }

        $fullName = $moduleName ? $moduleName . '.' . $functionName : $functionName;

        return $this->moduleRegistry->getHandler($fullName) !== null;
    }

    public function getFunction(array $args): mixed
    {
        $this->validateArgs($args, 1, 'get-function', true);

        $functionName = $args[0];
        $css          = $args[1] ?? false;

        if ($css) {
            return $functionName;
        }

        $handler = $this->moduleRegistry->getHandler($functionName);

        if ($handler !== null) {
            return new SassFunction($handler, $functionName);
        }

        if ($this->isUserDefinedFunction($functionName)) {
            return new SassUserFunction($this->context->functionHandler, $functionName);
        }

        throw new CompilationException("Function $functionName not found");
    }

    public function getMixin(array $args): SassMixin
    {
        $this->validateArgs($args, 1, 'get-mixin', true);

        $mixinName  = $args[0];
        $moduleName = $args[1] ?? null;

        if ($moduleName !== null) {
            $moduleMixins = $this->context->moduleHandler->getMixins($moduleName);

            if (isset($moduleMixins[$mixinName])) {
                $tempName = $moduleName . '.' . $mixinName;
                $this->context->mixinHandler->define(
                    $tempName,
                    $moduleMixins[$mixinName]['args'] ?? [],
                    $moduleMixins[$mixinName]['body'] ?? []
                );

                return new SassMixin($this->context->mixinHandler, $tempName);
            }

            throw new CompilationException("Mixin $mixinName not found in module $moduleName");
        }

        $mixins = $this->context->mixinHandler->getMixins()['mixins'];

        if (! isset($mixins[$mixinName])) {
            throw new CompilationException("Mixin $mixinName not found");
        }

        return new SassMixin($this->context->mixinHandler, $mixinName);
    }

    public function globalVariableExists(array $args): bool
    {
        $this->validateArgs($args, 1, 'global-variable-exists', true);

        $variableName = $args[0] ?? null;
        $moduleName   = $args[1] ?? null;

        if ($variableName === null) {
            return false;
        }

        $variableName = '$' . $variableName;

        if ($moduleName !== null) {
            $moduleVariables = $this->context->moduleHandler->getVariables($moduleName);

            return isset($moduleVariables[$variableName]);
        }

        return $this->context->variableHandler->globalVariableExists($variableName);
    }

    public function inspect(array $args): string
    {
        [$value] = $this->validateArgs($args, 1, 'inspect');

        if ($value instanceof SassList) {
            $items     = array_map(fn($item): string => $this->inspect([$item]), $value->value);
            $separator = $value->separator === 'comma' ? ', ' : ' ';
            $content   = implode($separator, $items);

            return $value->bracketed ? '[' . $content . ']' : $content;
        }

        if ($value instanceof SassMap) {
            $pairs = [];

            foreach ($value->value as $key => $val) {
                $keyStr = is_string($key) ? $key : $this->inspect([$key]);
                $valStr = $this->inspect([$val]);
                $pairs[] = $keyStr . ': ' . $valStr;
            }

            return '(' . implode(', ', $pairs) . ')';
        }

        // Handle array values (from evaluator)
        if (is_array($value) && isset($value['value'])) {
            if (isset($value['unit'])) {
                return $value['value'] . $value['unit'];
            }

            return (string) $value['value'];
        }

        // Handle plain arrays (maps)
        if (is_array($value)) {
            $pairs = [];
            foreach ($value as $key => $val) {
                $keyStr = $key; // Keys in array maps are usually strings
                $valStr = $this->inspect([$val]);
                $pairs[] = $keyStr . ': ' . $valStr;
            }

            return '(' . implode(', ', $pairs) . ')';
        }

        if ($value instanceof HexColorNode) {
            return $this->inspect([$value->value]);
        }

        if (is_string($value)) {
            $parsed = ColorParser::HEX->parse($value);

            if ($parsed !== null) {
                $named = ColorSerializer::getNamedColor($parsed['r'], $parsed['g'], $parsed['b']);
                if ($named !== null) {
                    return $named;
                }
            }

            return StringFormatter::forceQuoteString(addslashes($value));
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return 'null';
        }

        if (is_callable($value)) {
            return 'function';
        }

        if ($value instanceof SassColor) {
            return ColorSerializer::getNamedColor(
                (int) $value->getRed(),
                (int) $value->getGreen(),
                (int) $value->getBlue()
            ) ?? (string) $value;
        }

        return (string) $value;
    }

    public function keywords(array $args): SassMap
    {
        $this->validateArgs($args, 0, 'keywords', true);

        if (count($args) === 0) {
            return new SassMap([]);
        }

        $first = reset($args);

        // Handle rest parameter case - collect named arguments
        if ($first instanceof SassMap && count($args) === 1) {
            return $first;
        }

        // Check if we have named arguments (associative array)
        $namedArgs = [];
        foreach ($args as $key => $value) {
            if (is_string($key)) {
                // Remove $ prefix if present
                $cleanKey = str_starts_with($key, '$') ? substr($key, 1) : $key;
                $namedArgs[$cleanKey] = $value;
            }
        }

        if (! empty($namedArgs)) {
            return new SassMap($namedArgs);
        }

        return new SassMap([]);
    }

    public function mixinExists(array $args): bool
    {
        $this->validateArgs($args, 1, 'mixin-exists', true);

        $mixinName  = $args[0] ?? null;
        $moduleName = $args[1] ?? null;

        if ($mixinName === null) {
            return false;
        }

        if ($moduleName !== null) {
            $moduleMixins = $this->context->moduleHandler->getMixins($moduleName);

            return isset($moduleMixins[$mixinName]);
        }

        $mixins = $this->context->mixinHandler->getMixins()['mixins'];

        return isset($mixins[$mixinName]);
    }

    public function moduleFunctions(array $args): SassMap
    {
        [$moduleName] = $this->validateArgs($args, 1, 'module-functions');

        if (! is_string($moduleName)) {
            throw new CompilationException('module-functions() argument must be a string');
        }

        $functionNames = $this->moduleRegistry->getFunctionsForModule($moduleName);

        $functions = [];

        foreach ($functionNames as $name) {
            $handler = $this->moduleRegistry->getHandler($name);

            if ($handler !== null) {
                $functions[$name] = new SassFunction($handler, $name);
            }
        }

        return new SassMap($functions);
    }

    public function moduleMixins(array $args): SassMap
    {
        [$moduleName] = $this->validateArgs($args, 1, 'module-mixins');

        if (! is_string($moduleName)) {
            throw new CompilationException('module-mixins() argument must be a string');
        }

        $mixinsData = $this->context->moduleHandler->getMixins($moduleName);

        $mixins = [];
        foreach ($mixinsData as $name => $mixin) {
            $this->context->mixinHandler->define($name, $mixin['args'] ?? [], $mixin['body'] ?? []);

            $mixins[$name] = new SassMixin($this->context->mixinHandler, $name);
        }

        return new SassMap($mixins);
    }

    public function moduleVariables(array $args): SassMap
    {
        [$moduleName] = $this->validateArgs($args, 1, 'module-variables');

        if (! is_string($moduleName)) {
            throw new CompilationException('module-variables() argument must be a string');
        }

        $properties = $this->context->moduleHandler->getVariables($moduleName);

        $variables = [];
        foreach ($properties as $name => $value) {
            $key = ltrim($name, '$');

            if ($value instanceof VariableDeclarationNode && $this->evaluator) {
                $variables[$key] = ($this->evaluator)($value->value);

                continue;
            }

            if (! is_array($value) || ! isset($value['type'])) {
                $variables[$key] = $value;
            }
        }

        return new SassMap($variables);
    }

    public function typeOf(array $args): string
    {
        [$value] = $this->validateArgs($args, 1, 'type-of');

        if ($value instanceof SassNumber) {
            return 'number';
        }

        if ($value instanceof SassColor || $value instanceof HexColorNode) {
            return 'color';
        }

        if ($value instanceof SassList) {
            return 'list';
        }

        if ($value instanceof SassMap) {
            return 'map';
        }

        if ($value instanceof SassMixin) {
            return 'mixin';
        }

        if (is_array($value) && isset($value['value'])) {
            return 'number';
        }

        if (is_array($value)) {
            return 'map';
        }

        if (is_numeric($value)) {
            return 'number';
        }

        if (is_string($value)) {
            return 'string';
        }

        if (is_bool($value)) {
            return 'bool';
        }

        if ($value === null) {
            return 'null';
        }

        if (is_callable($value)) {
            return 'function';
        }

        if ($value instanceof CalcValue) {
            return 'calculation';
        }

        return 'unknown';
    }

    public function variableExists(array $args): bool
    {
        $this->validateArgs($args, 1, 'variable-exists', true);

        $variableName = $args[0] ?? null;
        $moduleName   = $args[1] ?? null;

        if (! is_string($variableName)) {
            throw new CompilationException('variable-exists() argument must be a string');
        }

        $variableName = '$' . $variableName;

        if ($moduleName !== null) {
            $moduleVariables = $this->context->moduleHandler->getVariables($moduleName);

            return isset($moduleVariables[$variableName]);
        }

        try {
            $this->context->variableHandler->get($variableName);

            return true;
        } catch (CompilationException) {
            return false;
        }
    }

    private function isUserDefinedFunction(string $name): bool
    {
        $userFunctions = $this->context->functionHandler->getUserFunctions();

        return isset($userFunctions['userDefinedFunctions'][$name])
            || isset($userFunctions['customFunctions'][$name]);
    }
}
