<?php

declare(strict_types=1);

use DartSass\Exceptions\CompilationException;
use DartSass\Utils\Scope;
use Tests\ReflectionAccessor;

describe('Scope', function () {
    beforeEach(function () {
        $this->parentScope = mock(Scope::class);
        $this->parentScope->shouldReceive('getParent')->andReturnNull();

        $this->scope = new Scope($this->parentScope);
    });

    describe('hasMixin()', function () {
        it('returns true for mixin in current scope', function () {
            $this->scope->setMixin('test', [], []);

            expect($this->scope->hasMixin('test'))->toBeTrue();
        });

        it('returns true for mixin in parent scope', function () {
            $this->parentScope->shouldReceive('hasMixin')->with('test')->andReturn(true);

            expect($this->scope->hasMixin('test'))->toBeTrue();
        });

        it('returns false when mixin not found', function () {
            $this->parentScope->shouldReceive('hasMixin')->with('missing')->andReturn(false);

            expect($this->scope->hasMixin('missing'))->toBeFalse();
        });
    });

    describe('hasFunction()', function () {
        it('returns true for function in current scope', function () {
            $this->scope->setFunction('test', [], []);

            expect($this->scope->hasFunction('test'))->toBeTrue();
        });

        it('returns true for function in parent scope', function () {
            $this->parentScope->shouldReceive('hasFunction')->with('test')->andReturn(true);

            expect($this->scope->hasFunction('test'))->toBeTrue();
        });

        it('returns false when function not found', function () {
            $this->parentScope->shouldReceive('hasFunction')->with('missing')->andReturn(false);

            expect($this->scope->hasFunction('missing'))->toBeFalse();
        });
    });

    describe('setFunction()', function () {
        it('stores in global scope', function () {
            $scope = new Scope(null);
            $scope->setFunction('global_func', [], [], true);

            $globalScope = $scope->getGlobalScope();

            expect($globalScope->hasFunction('global_func'))->toBeTrue();
        });
    });

    describe('getFunction()', function () {
        it('throws CompilationException when function not found in chain', function () {
            $scope = new Scope(null);

            expect(fn() => $scope->getFunction('missing'))
                ->toThrow(CompilationException::class, 'Undefined function: missing');
        });
    });

    describe('hasVariable()', function () {
        it('returns true for variable in current scope', function () {
            $this->scope->setVariable('test', 'value');

            expect($this->scope->hasVariable('test'))->toBeTrue();
        });

        it('returns true for variable in parent scope', function () {
            $this->parentScope->shouldReceive('hasVariable')->with('test')->andReturn(true);

            expect($this->scope->hasVariable('test'))->toBeTrue();
        });

        it('returns false when variable not found', function () {
            $this->parentScope->shouldReceive('hasVariable')->with('missing')->andReturn(false);

            expect($this->scope->hasVariable('missing'))->toBeFalse();
        });
    });

    describe('setVariable()', function () {
        beforeEach(function () {
            $this->parentScope->shouldReceive('hasVariable')->andReturn(false);
        });

        it('does not overwrite existing variable when default is true', function () {
            $this->scope->setVariable('test', 'original');
            $this->scope->setVariable('test', 'new', default: true);

            expect($this->scope->getVariable('test'))->toBe('original');
        });

        it('sets variable when default is true and variable does not exist', function () {
            $this->scope->setVariable('new_var', 'value', default: true);

            expect($this->scope->getVariable('new_var'))->toBe('value');
        });

    });

    describe('setVariable()', function () {
        it('skips existing variable when default is true', function () {
            $scope    = new Scope(null);
            $accessor = new ReflectionAccessor($scope);
            $accessor->setProperty('variables', ['test' => 'original']);
            $accessor->callMethod('setVariableForce', ['test', 'new', true]);

            expect($scope->getVariable('test'))->toBe('original');
        });
    });

    describe('removeMixin()', function () {
        it('delegates removal to parent scope when mixin is absent locally', function () {
            $this->parentScope->shouldReceive('removeMixin')->once()->with('sharedMixin');

            $this->scope->removeMixin('sharedMixin');
        });
    });
});
