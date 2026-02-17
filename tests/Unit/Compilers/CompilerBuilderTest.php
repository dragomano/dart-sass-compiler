<?php

declare(strict_types=1);

use DartSass\Compilers\CompilerBuilder;
use DartSass\Compilers\RuntimeContext;
use DartSass\Exceptions\CompilationException;
use DartSass\Handlers\Builtins\IfFunctionHandler;
use DartSass\Handlers\Builtins\MetaModuleHandler;
use DartSass\Handlers\FunctionHandler;
use DartSass\Handlers\FunctionRouter;
use DartSass\Handlers\ModuleRegistry;
use DartSass\Loaders\LoaderInterface;
use DartSass\Modules\MetaModule;
use DartSass\Parsers\Syntax;
use DartSass\Utils\ResultFormatter;
use DartSass\Utils\ValueFormatter;
use Tests\ReflectionAccessor;

describe('CompilerBuilder', function () {
    it('builds lazy expression callback without runtime engine dependency', function () {
        $builder = new CompilerBuilder(
            ['style' => 'expanded'],
            mock(LoaderInterface::class)
        );

        $engine = $builder->build();
        $engineAccessor = new ReflectionAccessor($engine);

        $functionHandler = $engineAccessor->getProperty('functionHandler');
        expect($functionHandler)->toBeInstanceOf(FunctionHandler::class);

        $functionHandlerAccessor = new ReflectionAccessor($functionHandler);
        $router = $functionHandlerAccessor->getProperty('router');
        expect($router)->toBeInstanceOf(FunctionRouter::class);

        $routerAccessor = new ReflectionAccessor($router);
        $registry = $routerAccessor->getProperty('registry');
        expect($registry)->toBeInstanceOf(ModuleRegistry::class);

        $ifHandler = $registry->getHandler('if');
        expect($ifHandler)->toBeInstanceOf(IfFunctionHandler::class);

        $ifHandlerAccessor = new ReflectionAccessor($ifHandler);
        $expression = $ifHandlerAccessor->getProperty('expression');
        expect($expression)->toBeInstanceOf(Closure::class);

        $reflection = new ReflectionFunction($expression);
        $usedVars = $reflection->getClosureUsedVariables();
        expect($usedVars)->not->toHaveKey('runtime')
            ->and($ifHandler->handle('if', [true, 'a', 'b']))->toBe('a');
    });

    it('provides options and scss compiler callbacks for meta module', function () {
        $builder = new CompilerBuilder(
            ['style' => 'expanded'],
            mock(LoaderInterface::class)
        );

        $engine = $builder->build();
        $engineAccessor = new ReflectionAccessor($engine);

        $functionHandler = $engineAccessor->getProperty('functionHandler');
        $functionHandlerAccessor = new ReflectionAccessor($functionHandler);
        $router = $functionHandlerAccessor->getProperty('router');
        $routerAccessor = new ReflectionAccessor($router);
        $registry = $routerAccessor->getProperty('registry');

        $metaHandler = $registry->getHandler('meta.inspect');
        expect($metaHandler)->toBeInstanceOf(MetaModuleHandler::class);

        $metaHandlerAccessor = new ReflectionAccessor($metaHandler);
        $metaModule = $metaHandlerAccessor->getProperty('metaModule');
        expect($metaModule)->toBeInstanceOf(MetaModule::class);

        $metaModuleAccessor = new ReflectionAccessor($metaModule);
        $optionsResolver = $metaModuleAccessor->getProperty('optionsResolver');
        $scssCompiler = $metaModuleAccessor->getProperty('scssCompiler');

        expect($optionsResolver)->toBeInstanceOf(Closure::class)
            ->and($optionsResolver())->toBeArray()
            ->and($optionsResolver())->toHaveKey('style')
            ->and($scssCompiler)->toBeInstanceOf(Closure::class);
    });

    it('throws when meta module scss compiler callback is invoked without compiler engine', function () {
        $builder = new CompilerBuilder(
            ['style' => 'expanded'],
            mock(LoaderInterface::class)
        );

        $engine = $builder->build();
        $engineAccessor = new ReflectionAccessor($engine);

        $functionHandler = $engineAccessor->getProperty('functionHandler');
        $functionHandlerAccessor = new ReflectionAccessor($functionHandler);
        $router = $functionHandlerAccessor->getProperty('router');
        $routerAccessor = new ReflectionAccessor($router);
        $registry = $routerAccessor->getProperty('registry');

        $metaHandler = $registry->getHandler('meta.inspect');
        expect($metaHandler)->toBeInstanceOf(MetaModuleHandler::class);

        $metaHandlerAccessor = new ReflectionAccessor($metaHandler);
        $metaModule = $metaHandlerAccessor->getProperty('metaModule');
        $metaModuleAccessor = new ReflectionAccessor($metaModule);
        $scssCompiler = $metaModuleAccessor->getProperty('scssCompiler');
        expect($scssCompiler)->toBeInstanceOf(Closure::class);

        $reflection = new ReflectionFunction($scssCompiler);
        $usedVars = $reflection->getClosureUsedVariables();
        $usedVars['runtime']->engine = null;

        expect(fn() => $scssCompiler('a { color: red; }', Syntax::SCSS))
            ->toThrow(CompilationException::class, 'Compiler engine is not available');
    });

    it('delegates meta module scss compiler callback to compiler engine', function () {
        $builder = new CompilerBuilder(
            ['style' => 'expanded', 'sourceMap' => false],
            mock(LoaderInterface::class)
        );

        $engine = $builder->build();
        $engineAccessor = new ReflectionAccessor($engine);

        $functionHandler = $engineAccessor->getProperty('functionHandler');
        $functionHandlerAccessor = new ReflectionAccessor($functionHandler);
        $router = $functionHandlerAccessor->getProperty('router');
        $routerAccessor = new ReflectionAccessor($router);
        $registry = $routerAccessor->getProperty('registry');

        $metaHandler = $registry->getHandler('meta.inspect');
        expect($metaHandler)->toBeInstanceOf(MetaModuleHandler::class);

        $metaHandlerAccessor = new ReflectionAccessor($metaHandler);
        $metaModule = $metaHandlerAccessor->getProperty('metaModule');
        $metaModuleAccessor = new ReflectionAccessor($metaModule);
        $scssCompiler = $metaModuleAccessor->getProperty('scssCompiler');
        expect($scssCompiler)->toBeInstanceOf(Closure::class);

        $compiled = $scssCompiler('a { color: red; }', Syntax::SCSS);
        expect($compiled)->toContain('a {')
            ->and($compiled)->toContain('color: red;');
    });

    it('throws when runtime callbacks are invoked before runtime is initialized', function () {
        $builder = new CompilerBuilder(
            ['style' => 'expanded'],
            mock(LoaderInterface::class)
        );

        $builderAccessor = new ReflectionAccessor($builder);

        $runtime = new RuntimeContext();

        $callbacks = $builderAccessor->callMethod('createRuntimeCallbacks', [
            $runtime,
            new ResultFormatter(new ValueFormatter()),
        ]);

        expect(fn() => $callbacks['evaluateExpression']('value'))
            ->toThrow(CompilationException::class, 'Expression evaluators are not available')
            ->and(fn() => $callbacks['compileAst']([]))
            ->toThrow(CompilationException::class, 'Compiler engine is not available')
            ->and(fn() => $callbacks['compileDeclarations']([]))
            ->toThrow(CompilationException::class, 'Compiler engine is not available')
            ->and(fn() => $callbacks['evaluateInterpolation']('#{1 + 1}'))
            ->toThrow(CompilationException::class, 'Interpolation evaluator is not available')
            ->and(fn() => $callbacks['addMapping']([]))
            ->toThrow(CompilationException::class, 'Compiler engine is not available');

    });
})->covers(CompilerBuilder::class);
