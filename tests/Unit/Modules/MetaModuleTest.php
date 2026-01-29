<?php

declare(strict_types=1);

use DartSass\Compilers\CompilerContext;
use DartSass\Compilers\CompilerEngineInterface;
use DartSass\Exceptions\CompilationException;
use DartSass\Handlers\Builtins\ModuleHandlerInterface;
use DartSass\Handlers\FunctionHandler;
use DartSass\Handlers\MixinHandler;
use DartSass\Handlers\ModuleHandler;
use DartSass\Handlers\ModuleRegistry;
use DartSass\Handlers\VariableHandler;
use DartSass\Modules\MetaModule;
use DartSass\Parsers\Syntax;
use DartSass\Values\CalcValue;
use DartSass\Values\SassColor;
use DartSass\Values\SassList;
use DartSass\Values\SassMap;
use DartSass\Values\SassMixin;
use DartSass\Values\SassNumber;

beforeEach(function () {
    $this->moduleRegistry  = mock(ModuleRegistry::class);
    $this->mixinHandler    = mock(MixinHandler::class);
    $this->variableHandler = mock(VariableHandler::class);
    $this->moduleHandler   = mock(ModuleHandler::class);
    $this->functionHandler = mock(FunctionHandler::class);

    $this->functionHandler
        ->shouldReceive('getUserFunctions')
        ->andReturn(['customFunctions' => [], 'userDefinedFunctions' => []]);

    $this->context                  = mock(CompilerContext::class);
    $this->context->mixinHandler    = $this->mixinHandler;
    $this->context->variableHandler = $this->variableHandler;
    $this->context->moduleHandler   = $this->moduleHandler;
    $this->context->functionHandler = $this->functionHandler;
    $this->context->options         = [];
    $this->context->engine          = mock(CompilerEngineInterface::class);

    $this->metaModule = new MetaModule($this->moduleRegistry, $this->context);
});

describe('MetaModule', function () {
    describe('apply()', function () {
        it('applies SassMixin with arguments', function () {
            $this->mixinHandler
                ->shouldReceive('getMixin')
                ->with('testMixin')
                ->andReturn(['body' => []]);

            $this->mixinHandler
                ->shouldReceive('include')
                ->with('testMixin', ['arg1'], null)
                ->andReturn('mixinResult');

            $mixin = new SassMixin($this->mixinHandler, 'testMixin');

            expect($this->metaModule->apply([$mixin, 'arg1']))->toBe('mixinResult');
        });

        it('includes mixin with arguments', function () {
            $this->mixinHandler
                ->shouldReceive('hasMixin')
                ->with('testMixin')
                ->andReturn(true);

            $this->mixinHandler
                ->shouldReceive('include')
                ->with('testMixin', ['arg1'])
                ->andReturn('mixinResult');

            expect($this->metaModule->apply(['testMixin', 'arg1']))->toBe('mixinResult');
        });

        it('throws exception for unknown mixin', function () {
            $this->mixinHandler
                ->shouldReceive('hasMixin')
                ->with('unknownMixin')
                ->andReturn(false);

            expect(fn() => $this->metaModule->apply(['unknownMixin']))
                ->toThrow(CompilationException::class, 'Unknown mixin: unknownMixin');
        });

        it('throws exception for invalid first argument', function () {
            expect(fn() => $this->metaModule->apply([123]))
                ->toThrow(CompilationException::class);
        });

        it('throws exception for no arguments', function () {
            expect(fn() => $this->metaModule->apply([]))
                ->toThrow(CompilationException::class);
        });
    });

    describe('call()', function () {
        it('calls callable with arguments', function () {
            $callable = fn() => 'result';

            expect($this->metaModule->call([$callable, 'arg1', 'arg2']))->toBe('result');
        });

        it('handles function with arguments', function () {
            $handler = mock(ModuleHandlerInterface::class);
            $handler->shouldReceive('handle')->with('testFunc', ['arg1'])->andReturn('funcResult');

            $this->moduleRegistry
                ->shouldReceive('getHandler')
                ->with('testFunc')
                ->andReturn($handler);

            expect($this->metaModule->call(['testFunc', 'arg1']))->toBe('funcResult');
        });

        it('throws exception for unknown function', function () {
            $this->moduleRegistry
                ->shouldReceive('getHandler')
                ->with('unknownFunc')
                ->andReturn(null);

            $this->functionHandler
                ->shouldReceive('call')
                ->with('unknownFunc', [])
                ->andThrow(CompilationException::class);

            expect(fn() => $this->metaModule->call(['unknownFunc']))
                ->toThrow(CompilationException::class);
        });

        it('throws exception for invalid first argument', function () {
            expect(fn() => $this->metaModule->call([123]))
                ->toThrow(CompilationException::class);
        });

        it('throws exception for no arguments', function () {
            expect(fn() => $this->metaModule->call([]))
                ->toThrow(CompilationException::class);
        });
    });

    describe('loadCss()', function () {
        it('throws exception for non-string argument', function () {
            expect(fn() => $this->metaModule->loadCss([123]))
                ->toThrow(CompilationException::class, 'load-css() argument must be a string');
        });

        it('throws exception for invalid URL', function () {
            expect(fn() => $this->metaModule->loadCss(['https://']))
                ->toThrow(CompilationException::class, 'load-css() argument must be a valid URL');
        });

        it('throws exception for wrong number of arguments', function () {
            expect(fn() => $this->metaModule->loadCss([]))
                ->toThrow(CompilationException::class)
                ->and(fn() => $this->metaModule->loadCss(['url1', 'url2']))
                ->toThrow(CompilationException::class);
        });

        it('handles relative paths correctly', function () {
            $fixtureDir   = __DIR__ . '/../../../tests/Feature/Sass/fixtures';
            $testFileName = 'test_main.scss';
            $testFile     = $fixtureDir . DIRECTORY_SEPARATOR . $testFileName;

            $this->context->options['sourceFile'] = $fixtureDir . DIRECTORY_SEPARATOR . 'source.scss';

            expect(file_exists($testFile))->toBeTrue()
                ->and(is_readable($testFile))->toBeTrue();

            $this->context->engine = mock(CompilerEngineInterface::class);
            $this->context->engine
                ->shouldReceive('compileString')
                ->withArgs(function ($content, $syntax) {
                    return str_contains($content, '$border-radius') && $syntax instanceof Syntax;
                })
                ->andReturn('.button { border-radius: 5px; }');

            $result = $this->metaModule->loadCss([$testFileName]);
            expect($result)->toContain('border-radius: 5px');
        });
    });

    describe('calcArgs()', function () {
        it('returns SassList of calculation args', function () {
            $calc = new CalcValue('10px', '+', '20px');

            $result = $this->metaModule->calcArgs([$calc]);
            expect($result)->toBeInstanceOf(SassList::class)
                ->and($result->separator)->toBe('space')
                ->and($result->value)->toEqual(['10px', '+', '20px']);
        });

        it('throws exception for non-calculation', function () {
            expect(fn() => $this->metaModule->calcArgs(['not-calc']))
                ->toThrow(CompilationException::class);
        });

        it('throws exception for wrong number of arguments', function () {
            expect(fn() => $this->metaModule->calcArgs([]))
                ->toThrow(CompilationException::class)
                ->and(fn() => $this->metaModule->calcArgs([1, 2]))
                ->toThrow(CompilationException::class);
        });
    });

    describe('calcName()', function () {
        it('returns name of calculation', function () {
            $calc = new CalcValue('10px', '+', '20px');

            expect($this->metaModule->calcName([$calc]))->toBe('"calc"');
        });

        it('returns name for string calculation', function () {
            expect($this->metaModule->calcName(['calc(10px + 20px)']))->toBe('"calc"');
        });

        it('throws exception for invalid calculation argument', function () {
            expect(fn() => $this->metaModule->calcName([123]))
                ->toThrow(CompilationException::class, 'calc-name() argument must be a calculation');
        });

        it('throws exception for wrong number of arguments', function () {
            expect(fn() => $this->metaModule->calcName([]))
                ->toThrow(CompilationException::class)
                ->and(fn() => $this->metaModule->calcName([1, 2]))
                ->toThrow(CompilationException::class);
        });
    });

    describe('inspect()', function () {
        it('returns string representation of SassNumber', function () {
            $number = new SassNumber(42, 'px');

            expect($this->metaModule->inspect([$number]))->toBe('42px');
        });

        it('returns string representation of SassColor', function () {
            $color = SassColor::rgb(255, 0, 0);

            expect($this->metaModule->inspect([$color]))->toBe('red');
        });

        it('returns string representation of SassList', function () {
            $list = new SassList([1, 'hello'], 'space');

            expect($this->metaModule->inspect([$list]))->toBe('1 "hello"');
        });

        it('returns string representation of bracketed SassList', function () {
            $list = new SassList([1, 2], 'comma', true);

            expect($this->metaModule->inspect([$list]))->toBe('[1, 2]');
        });

        it('returns string representation of SassMap', function () {
            $map = new SassMap(['key' => 'value']);

            expect($this->metaModule->inspect([$map]))->toBe('(key: "value")');
        });

        it('returns quoted string for string value', function () {
            expect($this->metaModule->inspect(['hello']))->toBe('"hello"');
        });

        it('returns "true" or "false" for boolean', function () {
            expect($this->metaModule->inspect([true]))->toBe('true')
                ->and($this->metaModule->inspect([false]))->toBe('false');
        });

        it('returns "null" for null', function () {
            expect($this->metaModule->inspect([null]))->toBe('null');
        });

        it('returns "function" for callable', function () {
            $func = function () {};

            expect($this->metaModule->inspect([$func]))->toBe('function');
        });

        it('returns string representation of SassMixin', function () {
            $mixin = new SassMixin($this->mixinHandler, 'testMixin');

            expect($this->metaModule->inspect([$mixin]))->toBe('testMixin');
        });

        it('returns string representation of CalcValue', function () {
            $calc = new CalcValue('100%', '+', '10px');

            expect($this->metaModule->inspect([$calc]))->toBe('calc(100% + 10px)');
        });

        it('returns string for unknown type', function () {
            expect($this->metaModule->inspect([123]))->toBe('123');
        });

        it('returns value from array with value key', function () {
            expect($this->metaModule->inspect([['value' => 42]]))->toBe('42');
        });

        it('returns string representation of plain array', function () {
            expect($this->metaModule->inspect([['key' => 'value']]))->toBe('(key: "value")');
        });

        it('throws exception for wrong number of arguments', function () {
            expect(fn() => $this->metaModule->inspect([]))
                ->toThrow(CompilationException::class)
                ->and(fn() => $this->metaModule->inspect([1, 2]))
                ->toThrow(CompilationException::class);
        });
    });

    describe('typeOf()', function () {
        it('returns "number" for SassNumber', function () {
            $number = new SassNumber(42);

            expect($this->metaModule->typeOf([$number]))->toBe('number');
        });

        it('returns "color" for SassColor', function () {
            $color = SassColor::rgb(255, 0, 0);

            expect($this->metaModule->typeOf([$color]))->toBe('color');
        });

        it('returns "list" for SassList', function () {
            $list = new SassList([1, 2, 3]);

            expect($this->metaModule->typeOf([$list]))->toBe('list');
        });

        it('returns "map" for SassMap', function () {
            $map = new SassMap([]);

            expect($this->metaModule->typeOf([$map]))->toBe('map');
        });

        it('returns "string" for string', function () {
            expect($this->metaModule->typeOf(['hello']))->toBe('string');
        });

        it('returns "bool" for boolean', function () {
            expect($this->metaModule->typeOf([true]))->toBe('bool');
        });

        it('returns "null" for null', function () {
            expect($this->metaModule->typeOf([null]))->toBe('null');
        });

        it('returns "function" for callable', function () {
            $func = function () {};

            expect($this->metaModule->typeOf([$func]))->toBe('function');
        });

        it('returns "mixin" for SassMixin', function () {
            $mixin = new SassMixin($this->mixinHandler, 'testMixin');

            expect($this->metaModule->typeOf([$mixin]))->toBe('mixin');
        });

        it('returns "calculation" for CalcValue', function () {
            $calc = new CalcValue('10px', '+', '20px');

            expect($this->metaModule->typeOf([$calc]))->toBe('calculation');
        });

        it('returns "number" for array with value key', function () {
            expect($this->metaModule->typeOf([['value' => 42]]))->toBe('number');
        });

        it('returns "map" for plain array', function () {
            expect($this->metaModule->typeOf([['key' => 'value']]))->toBe('map');
        });

        it('returns "unknown" for unknown type', function () {
            expect($this->metaModule->typeOf([new stdClass()]))->toBe('unknown');
        });

        it('throws exception for wrong number of arguments', function () {
            expect(fn() => $this->metaModule->typeOf([]))
                ->toThrow(CompilationException::class)
                ->and(fn() => $this->metaModule->typeOf([1, 2]))
                ->toThrow(CompilationException::class);
        });
    });

    describe('keywords()', function () {
        it('returns empty SassMap', function () {
            $result = $this->metaModule->keywords([]);

            expect($result)->toBeInstanceOf(SassMap::class)
                ->and($result->value)->toBeEmpty();
        });

        it('returns SassMap when first arg is SassMap', function () {
            $map = new SassMap(['key' => 'value']);

            $result = $this->metaModule->keywords([$map]);
            expect($result)->toBe($map);
        });

        it('returns SassMap with named arguments', function () {
            $args = ['key1' => 'value1', 'key2' => 'value2'];

            $result = $this->metaModule->keywords($args);
            expect($result)->toBeInstanceOf(SassMap::class)
                ->and($result->value)->toBe(['key1' => 'value1', 'key2' => 'value2']);
        });

        it('removes $ prefix from keys', function () {
            $args = ['$key1' => 'value1', 'key2' => 'value2'];

            $result = $this->metaModule->keywords($args);
            expect($result)->toBeInstanceOf(SassMap::class)
                ->and($result->value)->toBe(['key1' => 'value1', 'key2' => 'value2']);
        });

        it('returns empty SassMap for args without string keys', function () {
            $args = [1, 'value', true];

            $result = $this->metaModule->keywords($args);
            expect($result)->toBeInstanceOf(SassMap::class)
                ->and($result->value)->toBeEmpty();
        });
    });

    describe('functionExists()', function () {
        it('returns true when function exists', function () {
            $this->moduleRegistry
                ->shouldReceive('getHandler')
                ->with('testFunc')
                ->andReturn(mock(ModuleHandlerInterface::class));

            expect($this->metaModule->functionExists(['testFunc']))->toBeTrue();
        });

        it('returns false when function does not exist', function () {
            $this->moduleRegistry
                ->shouldReceive('getHandler')
                ->with('nonExistent')
                ->andReturn(null);

            expect($this->metaModule->functionExists(['nonExistent']))->toBeFalse();
        });

        it('returns true for module function', function () {
            $this->moduleRegistry
                ->shouldReceive('getHandler')
                ->with('module.testFunc')
                ->andReturn(mock(ModuleHandlerInterface::class));

            expect($this->metaModule->functionExists(['testFunc', 'module']))->toBeTrue();
        });

        it('returns false when function name is null', function () {
            expect($this->metaModule->functionExists([null]))->toBeFalse();
        });

        it('throws exception when no arguments', function () {
            expect(fn() => $this->metaModule->functionExists([]))
                ->toThrow(CompilationException::class);
        });
    });

    describe('getFunction()', function () {
        it('returns callable for existing function', function () {
            $handler = mock(ModuleHandlerInterface::class);
            $this->moduleRegistry
                ->shouldReceive('getHandler')
                ->with('testFunc')
                ->andReturn($handler);

            $result = $this->metaModule->getFunction(['testFunc']);
            expect($result)->toBeCallable();
        });

        it('throws exception for non-existing function', function () {
            $this->moduleRegistry
                ->shouldReceive('getHandler')
                ->with('nonExistent')
                ->andReturn(null);

            $this->functionHandler
                ->shouldReceive('exists')
                ->with('nonExistent')
                ->andReturn(false);

            expect(fn() => $this->metaModule->getFunction(['nonExistent']))
                ->toThrow(CompilationException::class);
        });

        it('returns function name when css is true', function () {
            $result = $this->metaModule->getFunction(['testFunc', true]);
            expect($result)->toBe('testFunc');
        });

        it('throws exception when no arguments', function () {
            expect(fn() => $this->metaModule->getFunction([]))
                ->toThrow(CompilationException::class);
        });
    });

    describe('mixinExists()', function () {
        it('returns true when mixin exists', function () {
            $this->mixinHandler->shouldReceive('hasMixin')
                ->with('testMixin')
                ->andReturn(true);

            expect($this->metaModule->mixinExists(['testMixin']))->toBeTrue();
        });

        it('returns false when mixin does not exist', function () {
            $this->mixinHandler->shouldReceive('hasMixin')
                ->with('nonExistent')
                ->andReturn(false);

            expect($this->metaModule->mixinExists(['nonExistent']))->toBeFalse();
        });

        it('checks mixin in module', function () {
            $this->moduleHandler
                ->shouldReceive('getMixins')
                ->with('module')
                ->andReturn(['testMixin' => []]);

            expect($this->metaModule->mixinExists(['testMixin', 'module']))->toBeTrue();
        });

        it('returns false when mixin name is null', function () {
            expect($this->metaModule->mixinExists([null]))->toBeFalse();
        });

        it('throws exception when no arguments', function () {
            expect(fn() => $this->metaModule->mixinExists([]))
                ->toThrow(CompilationException::class);
        });
    });

    describe('getMixin()', function () {
        it('throws exception for non-existing mixin', function () {
            $this->mixinHandler->shouldReceive('hasMixin')
                ->with('nonExistent')
                ->andReturn(false);

            expect(fn() => $this->metaModule->getMixin(['nonExistent']))
                ->toThrow(CompilationException::class);
        });

        it('returns mixin from module', function () {
            $this->moduleHandler
                ->shouldReceive('getMixins')
                ->with('testModule')
                ->andReturn(['testMixin' => ['args' => [], 'body' => ['rule']]]);

            $this->mixinHandler
                ->shouldReceive('define')
                ->with('testModule.testMixin', [], ['rule']);

            $result = $this->metaModule->getMixin(['testMixin', 'testModule']);
            expect($result)->toBeInstanceOf(SassMixin::class);
        });

        it('throws exception for mixin not found in module', function () {
            $this->moduleHandler
                ->shouldReceive('getMixins')
                ->with('testModule')
                ->andReturn([]);

            expect(fn() => $this->metaModule->getMixin(['testMixin', 'testModule']))
                ->toThrow(CompilationException::class, 'Mixin testMixin not found in module testModule');
        });

        it('throws exception when no arguments', function () {
            expect(fn() => $this->metaModule->getMixin([]))
                ->toThrow(CompilationException::class);
        });
    });

    describe('acceptsContent()', function () {
        it('returns true when SassMixin accepts content', function () {
            $this->mixinHandler
                ->shouldReceive('getMixin')
                ->with('testMixin')
                ->andReturn(['body' => ['@content']]);

            $mixin = new SassMixin($this->mixinHandler, 'testMixin');

            expect($this->metaModule->acceptsContent([$mixin]))->toBeTrue();
        });

        it('returns false when SassMixin does not accept content', function () {
            $this->mixinHandler
                ->shouldReceive('getMixin')
                ->with('testMixin')
                ->andReturn(['body' => ['some rule']]);

            $mixin = new SassMixin($this->mixinHandler, 'testMixin');

            expect($this->metaModule->acceptsContent([$mixin]))->toBeFalse();
        });

        it('returns false for invalid mixin', function () {
            $this->mixinHandler->shouldReceive('getMixin')
                ->with('string')
                ->andReturn(['body' => []]);

            expect($this->metaModule->acceptsContent(['string']))->toBeFalse();
        });

        it('returns false for non-callable mixin', function () {
            expect($this->metaModule->acceptsContent([123]))->toBeFalse();
        });

        it('returns false for callable mixin without mixinName', function () {
            $callable = function () {};

            expect($this->metaModule->acceptsContent([$callable]))->toBeFalse();
        });

        it('returns acceptsContent result for callable mixin with mixinName', function () {
            $callable = new class () {
                public string $mixinName = 'testMixin';

                public function __invoke() {}

                public function __toString()
                {
                    return $this->mixinName;
                }
            };

            $this->mixinHandler
                ->shouldReceive('getMixin')
                ->with('testMixin')
                ->andReturn(['body' => ['@content']]);

            expect($this->metaModule->acceptsContent([$callable]))->toBeTrue();
        });

        it('throws exception for wrong number of arguments', function () {
            expect(fn() => $this->metaModule->acceptsContent([]))
                ->toThrow(CompilationException::class)
                ->and(fn() => $this->metaModule->acceptsContent([1, 2]))
                ->toThrow(CompilationException::class);
        });
    });

    describe('contentExists()', function () {
        it('returns true when content exists', function () {
            $this->mixinHandler
                ->shouldReceive('hasContent')
                ->andReturn(true);

            expect($this->metaModule->contentExists([]))->toBeTrue();
        });

        it('returns false when no content', function () {
            $this->mixinHandler
                ->shouldReceive('hasContent')
                ->andReturn(false);

            expect($this->metaModule->contentExists([]))->toBeFalse();
        });

        it('throws exception with arguments', function () {
            expect(fn() => $this->metaModule->contentExists(['arg']))
                ->toThrow(CompilationException::class);
        });
    });

    describe('variableExists()', function () {
        it('returns true when variable exists in scope', function () {
            $this->variableHandler->shouldReceive('get')->with('$testVar')->andReturn('value');

            expect($this->metaModule->variableExists(['testVar']))->toBeTrue();
        });

        it('returns false when variable does not exist', function () {
            $this->variableHandler
                ->shouldReceive('get')
                ->with('$nonExistent')
                ->andThrow(CompilationException::class);

            expect($this->metaModule->variableExists(['nonExistent']))->toBeFalse();
        });

        it('returns true when variable exists in module', function () {
            $this->moduleHandler
                ->shouldReceive('getVariables')
                ->with('module')
                ->andReturn(['$testVar' => 'value']);

            expect($this->metaModule->variableExists(['testVar', 'module']))->toBeTrue();
        });

        it('throws exception when variable name is not a string', function () {
            expect(fn() => $this->metaModule->variableExists([123]))
                ->toThrow(CompilationException::class, 'variable-exists() argument must be a string');
        });

        it('throws exception when no arguments', function () {
            expect(fn() => $this->metaModule->variableExists([]))
                ->toThrow(CompilationException::class);
        });
    });

    describe('globalVariableExists()', function () {
        it('returns true when global variable exists', function () {
            $this->variableHandler
                ->shouldReceive('globalExists')
                ->with('$testVar')
                ->andReturn(true);

            expect($this->metaModule->globalVariableExists(['testVar']))->toBeTrue();
        });

        it('returns false when global variable does not exist', function () {
            $this->variableHandler
                ->shouldReceive('globalExists')
                ->with('$nonExistent')
                ->andReturn(false);

            expect($this->metaModule->globalVariableExists(['nonExistent']))->toBeFalse();
        });

        it('returns true when variable exists in module global', function () {
            $this->moduleHandler->shouldReceive('getGlobalVariables')->andReturn([]);
            $this->moduleHandler
                ->shouldReceive('getVariables')
                ->with('module')
                ->andReturn(['$testVar' => 'value']);

            expect($this->metaModule->globalVariableExists(['testVar', 'module']))->toBeTrue();
        });

        it('returns false when variable name is null', function () {
            expect($this->metaModule->globalVariableExists([null]))->toBeFalse();
        });

        it('throws exception when no arguments', function () {
            expect(fn() => $this->metaModule->globalVariableExists([]))
                ->toThrow(CompilationException::class);
        });
    });

    describe('moduleFunctions()', function () {
        it('returns functions for module', function () {
            $this->moduleRegistry
                ->shouldReceive('getFunctionsForModule')
                ->with('module')
                ->andReturn(['func1', 'func2']);

            $mockHandler1 = mock(ModuleHandlerInterface::class);
            $mockHandler1->shouldReceive('handle')
                ->andReturn('result1');

            $mockHandler2 = mock(ModuleHandlerInterface::class);
            $mockHandler2->shouldReceive('handle')
                ->andReturn('result2');

            $this->moduleRegistry
                ->shouldReceive('getHandler')
                ->with('func1')
                ->andReturn($mockHandler1);

            $this->moduleRegistry
                ->shouldReceive('getHandler')
                ->with('func2')
                ->andReturn($mockHandler2);

            $result = $this->metaModule->moduleFunctions(['module']);
            expect($result)->toBeInstanceOf(SassMap::class)
                ->and($result->value)->toHaveKey('func1')
                ->and($result->value)->toHaveKey('func2')
                ->and($result->value['func1'])->toBeCallable()
                ->and($result->value['func2'])->toBeCallable();
        });

        it('throws exception for non-string module', function () {
            expect(fn() => $this->metaModule->moduleFunctions([123]))
                ->toThrow(CompilationException::class);
        });

        it('throws exception for wrong number of arguments', function () {
            expect(fn() => $this->metaModule->moduleFunctions([]))
                ->toThrow(CompilationException::class)
                ->and(fn() => $this->metaModule->moduleFunctions(['mod', 'extra']))
                ->toThrow(CompilationException::class);
        });
    });

    describe('moduleVariables()', function () {
        it('returns variables for module', function () {
            $this->moduleHandler
                ->shouldReceive('getVariables')
                ->with('module')
                ->andReturn(['var1' => 'value1', 'var2' => 'value2', 'mixin1' => ['type' => 'mixin']]);

            $result = $this->metaModule->moduleVariables(['module']);
            expect($result)->toBeInstanceOf(SassMap::class)
                ->and($result->value)->toEqual(['var1' => 'value1', 'var2' => 'value2']);
        });

        it('throws exception for non-string module', function () {
            expect(fn() => $this->metaModule->moduleVariables([123]))
                ->toThrow(CompilationException::class);
        });
    });

    describe('moduleMixins()', function () {
        it('returns mixins for module', function () {
            $this->moduleHandler
                ->shouldReceive('getMixins')
                ->with('module')
                ->andReturn(['mixin1' => [], 'mixin2' => []]);

            $this->mixinHandler->shouldReceive('define')->andReturn(null);

            $result = $this->metaModule->moduleMixins(['module']);
            expect($result)->toBeInstanceOf(SassMap::class)
                ->and($result->value)->toHaveKey('mixin1')
                ->and($result->value)->toHaveKey('mixin2')
                ->and($result->value['mixin1'])->toBeInstanceOf(SassMixin::class)
                ->and($result->value['mixin2'])->toBeInstanceOf(SassMixin::class);
        });

        it('throws exception for non-string module', function () {
            expect(fn() => $this->metaModule->moduleMixins([123]))
                ->toThrow(CompilationException::class);
        });
    });

    describe('featureExists()', function () {
        it('returns true for supported features', function () {
            expect($this->metaModule->featureExists(['custom-property']))->toBeTrue();
        });

        it('returns false for unsupported features', function () {
            expect($this->metaModule->featureExists(['unsupported-feature']))->toBeFalse();
        });

        it('throws exception for non-string argument', function () {
            expect(fn() => $this->metaModule->featureExists([123]))
                ->toThrow(CompilationException::class);
        });

        it('throws exception for wrong number of arguments', function () {
            expect(fn() => $this->metaModule->featureExists([]))
                ->toThrow(CompilationException::class)
                ->and(fn() => $this->metaModule->featureExists(['feat1', 'feat2']))
                ->toThrow(CompilationException::class);
        });
    });
})->covers(MetaModule::class);
