<?php

declare(strict_types=1);

use DartSass\Compilers\Environment;
use DartSass\Exceptions\CompilationException;
use DartSass\Handlers\VariableHandler;

beforeEach(function () {
    $this->environment = new Environment();
    $this->variableHandler = new VariableHandler($this->environment);
});

describe('VariableHandler', function () {
    describe('constructor', function () {
        it('creates instance', function () {
            expect($this->variableHandler)->toBeInstanceOf(VariableHandler::class);
        });
    });

    describe('define and get methods', function () {
        it('defines and retrieves a variable', function () {
            $this->variableHandler->define('testVar', 'value');

            expect($this->variableHandler->get('testVar'))->toBe('value');
        });

        it('defines a global variable', function () {
            $this->variableHandler->define('globalVar', 'globalValue', true);

            expect($this->variableHandler->get('globalVar'))->toBe('globalValue');
        });

        it('does not override existing variable when using default flag', function () {
            $this->variableHandler->define('testVar', 'original');
            $this->variableHandler->define('testVar', 'new', false, true);

            expect($this->variableHandler->get('testVar'))->toBe('original');
        });

        it('overrides existing variable when not using default flag', function () {
            $this->variableHandler->define('testVar', 'original');
            $this->variableHandler->define('testVar', 'new');

            expect($this->variableHandler->get('testVar'))->toBe('new');
        });

        it('defines default global variable', function () {
            $this->variableHandler->define('globalVar', 'globalValue', true);
            $this->variableHandler->define('globalVar', 'newGlobal', true, true);

            expect($this->variableHandler->get('globalVar'))->toBe('globalValue');
        });

        it('allows null values', function () {
            $this->variableHandler->define('nullVar', null);

            expect($this->variableHandler->get('nullVar'))->toBeNull();
        });

        it('throws exception for undefined variable', function () {
            expect(fn() => $this->variableHandler->get('undefinedVar'))
                ->toThrow(CompilationException::class, 'Undefined variable: undefinedVar');
        });

        it('checks if variable exists', function () {
            $this->variableHandler->define('existsVar', 'val');
            expect($this->variableHandler->exists('existsVar'))->toBeTrue()
                ->and($this->variableHandler->exists('undefined'))->toBeFalse();
        });

        it('checks if global variable exists', function () {
            $this->variableHandler->define('globalVar', 'val', true);
            $this->environment->enterScope();

            expect($this->variableHandler->globalExists('globalVar'))->toBeTrue()
                ->and($this->variableHandler->globalExists('undefined'))->toBeFalse();
        });
    });

    describe('scope interactions', function () {
        it('accesses variables across scopes', function () {
            $this->environment->enterScope();
            $this->variableHandler->define('scopedVar', 'scopedValue');

            expect($this->variableHandler->get('scopedVar'))->toBe('scopedValue');

            $this->environment->exitScope();

            expect(fn() => $this->variableHandler->get('scopedVar'))
                ->toThrow(CompilationException::class);
        });

        it('inherits variables from parent scopes', function () {
            $this->variableHandler->define('inheritedVar', 'inheritedValue');
            $this->environment->enterScope();

            expect($this->variableHandler->get('inheritedVar'))->toBe('inheritedValue');
        });

        it('updates inherited variables in child scope', function () {
            $this->variableHandler->define('var', 'parent');
            $this->environment->enterScope();
            $this->variableHandler->define('var', 'child');

            expect($this->variableHandler->get('var'))->toBe('child');

            $this->environment->exitScope();

            expect($this->variableHandler->get('var'))->toBe('child');
        });

        it('shadows variable if forced local definition logic used', function () {
            $this->variableHandler->define('var', 'parent');
            $this->environment->enterScope();

            $this->environment->getCurrentScope()->setVariable('var', 'child');

            expect($this->variableHandler->get('var'))->toBe('child');
        });
    });

    describe('edge cases', function () {
        it('handles empty variable name', function () {
            $this->variableHandler->define('', 'emptyName');

            expect($this->variableHandler->get(''))->toBe('emptyName');
        });

        it('handles numeric values', function () {
            $this->variableHandler->define('num', 42);

            expect($this->variableHandler->get('num'))->toBe(42);
        });

        it('handles array values', function () {
            $array = ['key' => 'value'];
            $this->variableHandler->define('arr', $array);

            expect($this->variableHandler->get('arr'))->toBe($array);
        });

        it('handles boolean values', function () {
            $this->variableHandler->define('bool', true);

            expect($this->variableHandler->get('bool'))->toBeTrue();
        });
    });
})->covers(VariableHandler::class);
