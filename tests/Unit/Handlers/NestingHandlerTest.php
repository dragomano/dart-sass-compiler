<?php

declare(strict_types=1);

use DartSass\Handlers\NestingHandler;

beforeEach(function () {
    $this->nestingHandler = new NestingHandler();
});

describe('NestingHandler', function () {
    describe('resolveSelector method', function () {
        it('returns selector unchanged when no parent selector', function () {
            $result = $this->nestingHandler->resolveSelector('.child', '');

            expect($result)->toBe('.child');
        });

        it('replaces & with parent selector', function () {
            $result = $this->nestingHandler->resolveSelector('&:hover', '.parent');

            expect($result)->toBe('.parent:hover');
        });

        it('handles multiple selectors with &', function () {
            $result = $this->nestingHandler->resolveSelector('.a &, .b &', '.parent');

            expect($result)->toBe('.a .parent, .b .parent');
        });

        it('resolves selector with child combinator >', function () {
            $result = $this->nestingHandler->resolveSelector('> .child', '.parent');

            expect($result)->toBe('.parent > .child');
        });

        it('resolves selector with adjacent sibling combinator +', function () {
            $result = $this->nestingHandler->resolveSelector('+ .sibling', '.parent');

            expect($result)->toBe('.parent + .sibling');
        });

        it('resolves selector with general sibling combinator ~', function () {
            $result = $this->nestingHandler->resolveSelector('~ .sibling', '.parent');

            expect($result)->toBe('.parent ~ .sibling');
        });

        it('resolves selector with combinator without space', function () {
            $result = $this->nestingHandler->resolveSelector('>.child', '.parent');

            expect($result)->toBe('.parent > .child');
        });

        it('appends selector to parent when no & or combinator', function () {
            $result = $this->nestingHandler->resolveSelector('.child', '.parent');

            expect($result)->toBe('.parent .child');
        });

        it('handles multiple parent selectors', function () {
            $result = $this->nestingHandler->resolveSelector('.child', '.parent1, .parent2');

            expect($result)->toBe('.parent1 .child, .parent2 .child');
        });

        it('handles multiple selectors in child', function () {
            $result = $this->nestingHandler->resolveSelector('.child1, .child2', '.parent');

            expect($result)->toBe('.parent .child1, .parent .child2');
        });

        it('combines multiple parents and children', function () {
            $result = $this->nestingHandler->resolveSelector('.child1, &.child2', '.parent1, .parent2');

            expect($result)->toBe('.parent1 .child1, .parent2 .child1, .parent1.child2, .parent2.child2');
        });

        it('handles complex nesting with combinators and &', function () {
            $result = $this->nestingHandler->resolveSelector('&.active, > .child', '.parent');

            expect($result)->toBe('.parent.active, .parent > .child');
        });

        it('normalizes selectors with combinators', function () {
            $result = $this->nestingHandler->resolveSelector(' >.child', '.parent');

            expect($result)->toBe('.parent > .child');
        });

        it('handles combinator without following selector', function () {
            $result = $this->nestingHandler->resolveSelector('>', '.parent');

            expect($result)->toBe('.parent >');
        });

        it('handles string selector unchanged', function () {
            $result = $this->nestingHandler->resolveSelector('.child', '');

            expect($result)->toBe('.child');
        });
    });

    describe('normalizeSelector method', function () {
        it('normalizes selector with spaces around combinators', function () {
            $accessor = new Tests\ReflectionAccessor($this->nestingHandler);

            $result = $accessor->callMethod('normalizeSelector', ['.parent > .child']);

            expect($result)->toBe('.parent > .child');
        });

        it('adds spaces around combinators without spaces', function () {
            $accessor = new Tests\ReflectionAccessor($this->nestingHandler);

            $result = $accessor->callMethod('normalizeSelector', ['.parent>.child']);

            expect($result)->toBe('.parent > .child');
        });

        it('handles multiple combinators', function () {
            $accessor = new Tests\ReflectionAccessor($this->nestingHandler);

            $result = $accessor->callMethod('normalizeSelector', ['.parent>.child+.sibling']);

            expect($result)->toBe('.parent > .child + .sibling');
        });

        it('trims trailing spaces', function () {
            $accessor = new Tests\ReflectionAccessor($this->nestingHandler);

            $result = $accessor->callMethod('normalizeSelector', ['  .parent  ']);

            expect($result)->toBe('.parent');
        });
    });
})->covers(NestingHandler::class);
