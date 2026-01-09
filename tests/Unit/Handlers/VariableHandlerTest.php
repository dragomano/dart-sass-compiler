<?php

declare(strict_types=1);

use DartSass\Exceptions\CompilationException;
use DartSass\Handlers\VariableHandler;

beforeEach(function () {
    $this->variableHandler = new VariableHandler();
});

describe('VariableHandler', function () {
    describe('constructor', function () {
        it('creates instance', function () {
            expect($this->variableHandler)->toBeInstanceOf(VariableHandler::class);
        });

        it('initializes with empty scopes and global variables', function () {
            $variables = $this->variableHandler->getVariables();
            expect($variables)->toBe([]);
        });
    });

    describe('define method', function () {
        it('defines a variable in current scope', function () {
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
    });

    describe('get method', function () {
        it('gets variable from current scope', function () {
            $this->variableHandler->define('localVar', 'localValue');

            expect($this->variableHandler->get('localVar'))->toBe('localValue');
        });

        it('gets variable from global scope', function () {
            $this->variableHandler->define('globalVar', 'globalValue', true);

            expect($this->variableHandler->get('globalVar'))->toBe('globalValue');
        });

        it('throws exception for undefined variable', function () {
            expect(fn() => $this->variableHandler->get('undefinedVar'))
                ->toThrow(CompilationException::class, 'Undefined variable: undefinedVar');
        });

        it('prioritizes local scope over global', function () {
            $this->variableHandler->define('var', 'global', true);
            $this->variableHandler->define('var', 'local');

            expect($this->variableHandler->get('var'))->toBe('local');
        });
    });

    describe('scope management', function () {
        it('enters and exits scope', function () {
            $this->variableHandler->enterScope();
            $this->variableHandler->define('scopedVar', 'scopedValue');

            expect($this->variableHandler->get('scopedVar'))->toBe('scopedValue');

            $this->variableHandler->exitScope();

            expect(fn() => $this->variableHandler->get('scopedVar'))
                ->toThrow(CompilationException::class);
        });

        it('does not exit the last scope', function () {
            $this->variableHandler->define('rootVar', 'rootValue');
            $this->variableHandler->exitScope(); // Should not remove root scope

            expect($this->variableHandler->get('rootVar'))->toBe('rootValue');
        });

        it('inherits variables from parent scopes', function () {
            $this->variableHandler->define('inheritedVar', 'inheritedValue');
            $this->variableHandler->enterScope();

            expect($this->variableHandler->get('inheritedVar'))->toBe('inheritedValue');
        });

        it('overrides inherited variables in child scope', function () {
            $this->variableHandler->define('var', 'parent');
            $this->variableHandler->enterScope();
            $this->variableHandler->define('var', 'child');

            expect($this->variableHandler->get('var'))->toBe('child');

            $this->variableHandler->exitScope();

            expect($this->variableHandler->get('var'))->toBe('parent');
        });

        it('handles multiple nested scopes', function () {
            $this->variableHandler->define('level0', 'value0');
            $this->variableHandler->enterScope();
            $this->variableHandler->define('level1', 'value1');
            $this->variableHandler->enterScope();
            $this->variableHandler->define('level2', 'value2');

            expect($this->variableHandler->get('level0'))->toBe('value0')
                ->and($this->variableHandler->get('level1'))->toBe('value1')
                ->and($this->variableHandler->get('level2'))->toBe('value2');

            $this->variableHandler->exitScope();
            expect($this->variableHandler->get('level1'))->toBe('value1')
                ->and(fn() => $this->variableHandler->get('level2'))
                ->toThrow(CompilationException::class);

            $this->variableHandler->exitScope();
            expect($this->variableHandler->get('level0'))->toBe('value0')
                ->and(fn() => $this->variableHandler->get('level1'))
                ->toThrow(CompilationException::class);
        });
    });

    describe('getVariables method', function () {
        it('returns all variables from all scopes', function () {
            $this->variableHandler->define('globalVar', 'global', true);
            $this->variableHandler->define('localVar', 'local');
            $this->variableHandler->enterScope();
            $this->variableHandler->define('scopedVar', 'scoped');

            $variables = $this->variableHandler->getVariables();

            expect($variables)->toHaveKey('globalVar')
                ->and($variables)->toHaveKey('localVar')
                ->and($variables)->toHaveKey('scopedVar')
                ->and($variables['globalVar'])->toBe('global')
                ->and($variables['localVar'])->toBe('local')
                ->and($variables['scopedVar'])->toBe('scoped');
        });

        it('merges scopes correctly', function () {
            $this->variableHandler->define('var', 'root');
            $this->variableHandler->enterScope();
            $this->variableHandler->define('var', 'scoped');

            $variables = $this->variableHandler->getVariables();

            expect($variables['var'])->toBe('scoped');
        });

        it('returns empty array when no variables', function () {
            $variables = $this->variableHandler->getVariables();
            expect($variables)->toBe([]);
        });
    });

    describe('setVariables method', function () {
        it('sets global variables and clears scopes', function () {
            $this->variableHandler->define('oldVar', 'old');
            $this->variableHandler->enterScope();
            $this->variableHandler->define('scopedVar', 'scoped');

            $this->variableHandler->setVariables(['newVar' => 'newValue']);

            $variables = $this->variableHandler->getVariables();
            expect($variables)->toBe(['newVar' => 'newValue'])
                ->and(fn() => $this->variableHandler->get('oldVar'))
                ->toThrow(CompilationException::class);

        });

        it('allows setting empty array', function () {
            $this->variableHandler->define('var', 'value');
            $this->variableHandler->setVariables([]);

            $variables = $this->variableHandler->getVariables();
            expect($variables)->toBe([]);
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

        it('preserves variable types', function () {
            $this->variableHandler->define('string', 'text');
            $this->variableHandler->define('int', 123);
            $this->variableHandler->define('float', 1.23);
            $this->variableHandler->define('bool', false);

            expect($this->variableHandler->get('string'))->toBeString()
                ->and($this->variableHandler->get('int'))->toBeInt()
                ->and($this->variableHandler->get('float'))->toBeFloat()
                ->and($this->variableHandler->get('bool'))->toBeBool();
        });

        it('handles define with default flag true', function () {
            $this->variableHandler->define('testVar', 'original');
            $this->variableHandler->define('testVar', 'new', false, true);

            expect($this->variableHandler->get('testVar'))->toBe('original');
        });

        it('handles define with overwrite flag true', function () {
            $this->variableHandler->define('testVar', 'original');
            $this->variableHandler->define('testVar', 'new');

            expect($this->variableHandler->get('testVar'))->toBe('new');
        });

        it('handles global define with default flag', function () {
            $this->variableHandler->define('globalVar', 'original', true);
            $this->variableHandler->define('globalVar', 'new', true, true);

            expect($this->variableHandler->get('globalVar'))->toBe('original');
        });

        it('handles setVariables with global variables', function () {
            $this->variableHandler->define('local', 'localVal');
            $this->variableHandler->define('global', 'globalVal', true);

            $this->variableHandler->setVariables(['newGlobal' => 'newVal']);

            expect($this->variableHandler->getVariables())->toBe(['newGlobal' => 'newVal'])
                ->and(fn() => $this->variableHandler->get('local'))->toThrow(CompilationException::class)
                ->and(fn() => $this->variableHandler->get('global'))->toThrow(CompilationException::class);
        });

        it('handles define with default flag when variable exists in current scope', function () {
            $this->variableHandler->enterScope();
            $this->variableHandler->define('scopedVar', 'original');
            $this->variableHandler->define('scopedVar', 'new', false, true);

            expect($this->variableHandler->get('scopedVar'))->toBe('original');
        });
    });
})->covers(VariableHandler::class);
