<?php

declare(strict_types=1);

use DartSass\Exceptions\CompilationException;
use DartSass\Modules\SelectorModule;
use DartSass\Values\SassList;

beforeEach(function () {
    $this->selectorModule = new SelectorModule();
});

describe('SelectorModule', function () {
    describe('parse()', function () {
        it('parses comma-separated selectors', function () {
            $result = $this->selectorModule->parse(['.foo, .bar']);

            expect($result)->toBeInstanceOf(SassList::class)
                ->and($result->value)->toBe(['.foo', '.bar'])
                ->and($result->separator)->toBe('comma');
        });

        it('parses single selector', function () {
            $result = $this->selectorModule->parse(['.foo']);

            expect($result->value)->toBe(['.foo'])
                ->and($result->separator)->toBe('comma');
        });

        it('throws exception for non-string argument', function () {
            expect(fn() => $this->selectorModule->parse([123]))
                ->toThrow(CompilationException::class);
        });

        it('throws exception for wrong number of arguments', function () {
            expect(fn() => $this->selectorModule->parse([]))
                ->toThrow(CompilationException::class);
        });

        it('handles empty string', function () {
            $result = $this->selectorModule->parse(['']);

            expect($result)->toBeInstanceOf(SassList::class)
                ->and($result->value)->toBe([])
                ->and($result->separator)->toBe('comma');
        });

        it('parses selectors with multiple spaces', function () {
            $result = $this->selectorModule->parse(['.foo  ,   .bar']);

            expect($result)->toBeInstanceOf(SassList::class)
                ->and($result->value)->toBe(['.foo', '.bar'])
                ->and($result->separator)->toBe('comma');
        });
    });

    describe('isSuperSelector()', function () {
        it('returns true for exact match', function () {
            expect($this->selectorModule->isSuperSelector(['.foo', '.foo']))->toBeTrue();
        });

        it('returns true when first is superselector of second', function () {
            expect($this->selectorModule->isSuperSelector(['.foo', '.container .foo']))->toBeTrue();
        });

        it('returns false when sub is not contained', function () {
            expect($this->selectorModule->isSuperSelector(['.foo', '.bar']))->toBeFalse();
        });

        it('throws exception for non-string arguments', function () {
            expect(fn() => $this->selectorModule->isSuperSelector(['.foo', 123]))
                ->toThrow(CompilationException::class);
        });

        it('handles complex selectors and combinators', function () {
            expect($this->selectorModule->isSuperSelector(['.foo > .bar', '.foo .bar']))->toBeFalse();
        });

        it('returns true for nested complex selectors', function () {
            expect($this->selectorModule->isSuperSelector(['.foo', '.container .foo']))->toBeTrue();
        });

        it('throws exception for empty arguments', function () {
            expect(fn() => $this->selectorModule->isSuperSelector([]))
                ->toThrow(
                    CompilationException::class,
                    'is-superselector() requires exactly two arguments'
                );
        });

        it('throws exception for one argument', function () {
            expect(fn() => $this->selectorModule->isSuperSelector(['.foo']))
                ->toThrow(
                    CompilationException::class,
                    'is-superselector() requires exactly two arguments'
                );
        });

        it('throws exception for three arguments', function () {
            expect(fn() => $this->selectorModule->isSuperSelector(['.foo', '.bar', '.baz']))
                ->toThrow(
                    CompilationException::class,
                    'is-superselector() requires exactly two arguments'
                );
        });

        it('returns true for selectors with pseudo-classes at end', function () {
            expect($this->selectorModule->isSuperSelector(['.foo', '.container .foo:hover']))->toBeTrue();
        });

        it('returns true when selector ends with target as last part', function () {
            expect($this->selectorModule->isSuperSelector(['.baz', '.bar .baz']))->toBeTrue();
        });
    });

    describe('simpleSelectors()', function () {
        it('splits complex selector into simple parts', function () {
            $result = $this->selectorModule->simpleSelectors(['div.foo:hover']);

            expect($result)->toBeInstanceOf(SassList::class)
                ->and($result->value)->toBe(['div', '.foo', ':hover'])
                ->and($result->separator)->toBe('space');
        });

        it('handles simple selector', function () {
            $result = $this->selectorModule->simpleSelectors(['.foo']);

            expect($result->value)->toBe(['.foo']);
        });

        it('throws exception for non-string argument', function () {
            expect(fn() => $this->selectorModule->simpleSelectors([123]))
                ->toThrow(CompilationException::class);
        });

        it('handles attributes and pseudo-selectors', function () {
            $result = $this->selectorModule->simpleSelectors(['div[data-attr="value"]:hover']);

            expect($result)->toBeInstanceOf(SassList::class)
                ->and($result->value)->toBe(['div', '[data-attr="value"]', ':hover'])
                ->and($result->separator)->toBe('space');
        });

        it('handles multiple pseudo-selectors', function () {
            $result = $this->selectorModule->simpleSelectors(['a:focus:hover']);

            expect($result)->toBeInstanceOf(SassList::class)
                ->and($result->value)->toBe(['a', ':focus', ':hover'])
                ->and($result->separator)->toBe('space');
        });

        it('throws exception for zero arguments', function () {
            expect(fn() => $this->selectorModule->simpleSelectors([]))
                ->toThrow(
                    CompilationException::class,
                    'simple-selectors() requires exactly one argument'
                );
        });

        it('throws exception for two arguments', function () {
            expect(fn() => $this->selectorModule->simpleSelectors(['.foo', '.bar']))
                ->toThrow(
                    CompilationException::class,
                    'simple-selectors() requires exactly one argument'
                );
        });
    });

    describe('append()', function () {
        it('appends selectors without space', function () {
            expect($this->selectorModule->append(['.foo', '.bar']))->toBe('.foo.bar');
        });

        it('handles combinator at end of first selector', function () {
            expect($this->selectorModule->append(['.foo +', '.bar']))->toBe('.foo+.bar');
        });

        it('throws exception for non-string arguments', function () {
            expect(fn() => $this->selectorModule->append(['.foo', 123]))
                ->toThrow(CompilationException::class);
        });

        it('appends multiple selectors with different combinators', function () {
            expect($this->selectorModule->append(['.foo >', '.bar, .baz']))->toBe('.foo>.bar, .foo>.baz');
        });

        it('handles multiple selectors without combinators', function () {
            expect($this->selectorModule->append(['.foo', '.bar, .baz']))->toBe('.foo.bar, .foo.baz');
        });

        it('throws exception for empty arguments', function () {
            expect(fn() => $this->selectorModule->append([]))
                ->toThrow(
                    CompilationException::class,
                    'selector-append() requires at least two arguments'
                );
        });

        it('throws exception for one argument', function () {
            expect(fn() => $this->selectorModule->append(['.foo']))
                ->toThrow(
                    CompilationException::class,
                    'selector-append() requires at least two arguments'
                );
        });
    });

    describe('extend()', function () {
        it('extends selector by adding extended version', function () {
            expect($this->selectorModule->extend(['.foo .bar', '.bar', '.baz']))
                ->toBe('.foo .bar, .foo .baz');
        });

        it('throws exception for non-string arguments', function () {
            expect(fn() => $this->selectorModule->extend(['.foo', '.bar', 123]))
                ->toThrow(CompilationException::class);
        });

        it('handles multiple occurrences and partial matching', function () {
            expect($this->selectorModule->extend(['.foo .bar, .baz .bar', '.bar', '.qux']))
                ->toBe('.foo .bar, .baz .bar, .foo .qux, .baz .qux');
        });

        it('extends with partial matching', function () {
            expect($this->selectorModule->extend(['.foo .bar', '.foo', '.baz']))
                ->toBe('.foo .bar, .baz .bar');
        });

        it('throws exception for empty arguments', function () {
            expect(fn() => $this->selectorModule->extend([]))
                ->toThrow(
                    CompilationException::class,
                    'selector-extend() requires exactly three arguments'
                );
        });

        it('throws exception for one argument', function () {
            expect(fn() => $this->selectorModule->extend(['.foo']))
                ->toThrow(
                    CompilationException::class,
                    'selector-extend() requires exactly three arguments'
                );
        });

        it('throws exception for two arguments', function () {
            expect(fn() => $this->selectorModule->extend(['.foo', '.bar']))
                ->toThrow(
                    CompilationException::class,
                    'selector-extend() requires exactly three arguments'
                );
        });

        it('throws exception for four arguments', function () {
            expect(fn() => $this->selectorModule->extend(['.foo', '.bar', '.baz', '.qux']))
                ->toThrow(
                    CompilationException::class,
                    'selector-extend() requires exactly three arguments'
                );
        });

        it('handles exact match replacement in extend', function () {
            expect($this->selectorModule->extend(['.bar', '.bar', '.baz']))
                ->toBe('.bar, .baz');
        });
    });

    describe('replace()', function () {
        it('replaces first occurrence', function () {
            expect($this->selectorModule->replace(['.foo .bar .baz', '.bar', '.qux']))
                ->toBe('.foo .qux .baz');
        });

        it('throws exception for non-string arguments', function () {
            expect(fn() => $this->selectorModule->replace(['.foo', '.bar', 123]))
                ->toThrow(CompilationException::class);
        });

        it('handles multiple occurrences', function () {
            expect($this->selectorModule->replace(['.foo .bar .baz .bar', '.bar', '.qux']))
                ->toBe('.foo .qux .baz .qux');
        });

        it('returns original when no matches', function () {
            expect($this->selectorModule->replace(['.foo .bar', '.baz', '.qux']))
                ->toBe('.foo .bar');
        });

        it('throws exception for zero arguments', function () {
            expect(fn() => $this->selectorModule->replace([]))
                ->toThrow(
                    CompilationException::class,
                    'selector-replace() requires exactly three arguments'
                );
        });

        it('throws exception for one argument', function () {
            expect(fn() => $this->selectorModule->replace(['.foo']))
                ->toThrow(
                    CompilationException::class,
                    'selector-replace() requires exactly three arguments'
                );
        });

        it('throws exception for two arguments', function () {
            expect(fn() => $this->selectorModule->replace(['.foo', '.bar']))
                ->toThrow(
                    CompilationException::class,
                    'selector-replace() requires exactly three arguments'
                );
        });

        it('throws exception for four arguments', function () {
            expect(fn() => $this->selectorModule->replace(['.foo', '.bar', '.baz', '.qux']))
                ->toThrow(
                    CompilationException::class,
                    'selector-replace() requires exactly three arguments'
                );
        });
    });

    describe('nest()', function () {
        it('joins selectors with space', function () {
            expect($this->selectorModule->nest(['.foo', '.bar']))->toBe('.foo .bar');
        });

        it('throws exception for non-string arguments', function () {
            expect(fn() => $this->selectorModule->nest(['.foo', 123]))
                ->toThrow(CompilationException::class);
        });

        it('handles multiple selectors', function () {
            expect($this->selectorModule->nest(['.foo, .bar', '.baz']))->toBe('.foo, .bar .baz');
        });

        it('throws exception for empty arguments', function () {
            expect(fn() => $this->selectorModule->nest([]))
                ->toThrow(
                    CompilationException::class,
                    'selector-nest() requires at least two arguments'
                );
        });

        it('throws exception for one argument', function () {
            expect(fn() => $this->selectorModule->nest(['.foo']))
                ->toThrow(
                    CompilationException::class,
                    'selector-nest() requires at least two arguments'
                );
        });
    });

    describe('unify()', function () {
        it('unifies compatible selectors', function () {
            $result = $this->selectorModule->unify(['div', '.foo']);
            expect($result)->toBe('div.foo');
        });

        it('returns null for incompatible selectors', function () {
            $result = $this->selectorModule->unify(['div', 'p']);
            expect($result)->toBeNull();
        });

        it('returns null for multiple IDs', function () {
            $result = $this->selectorModule->unify(['#id1', '#id2']);
            expect($result)->toBeNull();
        });

        it('throws exception for non-string arguments', function () {
            expect(fn() => $this->selectorModule->unify(['div', 123]))
                ->toThrow(CompilationException::class);
        });

        it('unifies with ID', function () {
            $result = $this->selectorModule->unify(['#id', '.foo']);
            expect($result)->toBe('.foo#id');
        });

        it('unifies multiple classes', function () {
            $result = $this->selectorModule->unify(['.foo', '.bar']);
            expect($result)->toBe('.foo.bar');
        });

        it('throws exception for zero arguments', function () {
            expect(fn() => $this->selectorModule->unify([]))
                ->toThrow(
                    CompilationException::class,
                    'selector-unify() requires exactly two arguments'
                );
        });

        it('throws exception for one argument', function () {
            expect(fn() => $this->selectorModule->unify(['.foo']))
                ->toThrow(
                    CompilationException::class,
                    'selector-unify() requires exactly two arguments'
                );
        });

        it('throws exception for three arguments', function () {
            expect(fn() => $this->selectorModule->unify(['.foo', '.bar', '.baz']))
                ->toThrow(
                    CompilationException::class,
                    'selector-unify() requires exactly two arguments'
                );
        });
    });
})->covers(SelectorModule::class);
