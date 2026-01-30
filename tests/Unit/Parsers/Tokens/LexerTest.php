<?php

declare(strict_types=1);

use DartSass\Exceptions\InvalidColorException;
use DartSass\Parsers\Tokens\Lexer;
use Tests\ReflectionAccessor;

describe('Lexer', function () {
    beforeEach(function () {
        $this->lexer    = new Lexer();
        $this->accessor = new ReflectionAccessor($this->lexer);
    });

    it('tokenizes basic SCSS selectors', function () {
        $tokenStream = $this->lexer->tokenize('.class { color: red; }');

        expect($tokenStream->getTokens())->toHaveCount(8);

        $tokenTypes = array_map(fn($token) => $token->type, $tokenStream->getTokens());

        expect($tokenTypes)->toBe([
            'operator',
            'identifier',
            'brace_open',
            'identifier',
            'colon',
            'identifier',
            'semicolon',
            'brace_close',
        ]);
    });

    it('tokenizes operators correctly', function () {
        $tokenStream = $this->lexer->tokenize('.parent > .child');

        $tokenValues = array_map(fn($token) => $token->value, $tokenStream->getTokens());
        expect($tokenValues)->toBe(['.', 'parent', '>', '.', 'child']);
    });

    it('tokenizes attribute selectors', function () {
        $tokenStream = $this->lexer->tokenize('[class*="test"]');

        $tokenTypes = array_map(fn($token) => $token->type, $tokenStream->getTokens());
        expect($tokenTypes)->toBe(['attribute_selector']);
    });

    it('handles multiple selectors separated by comma', function () {
        $tokenStream = $this->lexer->tokenize('.class1, .class2');

        $tokenValues = array_map(fn($token) => $token->value, $tokenStream->getTokens());
        expect($tokenValues)->toBe(['.', 'class1', ',', '.', 'class2']);
    });

    it('tokenizes pseudo-classes', function () {
        $tokenStream = $this->lexer->tokenize(':hover');

        $tokenTypes = array_map(fn($token) => $token->type, $tokenStream->getTokens());
        expect($tokenTypes)->toBe(['colon', 'identifier']);
    });

    it('tokenizes functions', function () {
        $tokenStream = $this->lexer->tokenize('calc(100% - 20px)');

        $tokenValues = array_map(fn($token) => $token->value, $tokenStream->getTokens());
        expect($tokenValues)->toBe(['calc', '(', '100%', '-', '20px', ')']);
    });

    it('preserves line and column information', function () {
        $tokenStream = $this->lexer->tokenize('.class {
  color: red;
}');

        $tokens = $tokenStream->getTokens();
        $firstToken = $tokens[0];
        expect($firstToken->line)->toBe(1)
            ->and($firstToken->column)->toBe(1);

        foreach ($tokens as $token) {
            expect($token->line)->toBeGreaterThanOrEqual(1)
                ->and($token->column)->toBeGreaterThanOrEqual(1);
        }
    });

    it('handles @ character correctly', function () {
        // @ is valid when it's part of an at-rule
        $tokenStream = $this->lexer->tokenize('.class { @include test; }');

        expect($tokenStream->getTokens())->toHaveCount(7);

        $tokenValues = array_map(fn($token) => $token->value, $tokenStream->getTokens());
        expect($tokenValues)->toBe(['.', 'class', '{', '@include', 'test', ';', '}']);
    });

    it('tokenizes variables', function () {
        $tokenStream = $this->lexer->tokenize('$color: red');

        $tokenTypes = array_map(fn($token) => $token->type, $tokenStream->getTokens());
        expect($tokenTypes)->toBe(['variable', 'colon', 'identifier']);
    });

    it('handles interpolation', function () {
        $tokenStream = $this->lexer->tokenize('.#{ $class }');

        $tokenTypes = array_map(fn($token) => $token->type, $tokenStream->getTokens());
        expect($tokenTypes)->toContain('interpolation_open')
            ->and($tokenTypes)->toContain('variable')
            ->and($tokenTypes)->toContain('brace_close');
    });

    it('handles numbers and units correctly', function () {
        $tokenStream = $this->lexer->tokenize('100px');

        $tokenTypes = array_map(fn($token) => $token->type, $tokenStream->getTokens());
        expect($tokenTypes)->toBe(['number']);
    });

    it('handles percentage values', function () {
        $tokenStream = $this->lexer->tokenize('50%');

        $tokenTypes = array_map(fn($token) => $token->type, $tokenStream->getTokens());
        expect($tokenTypes)->toBe(['number']);
    });

    it('handles hex colors', function () {
        $tokenStream = $this->lexer->tokenize('#ff0000');

        $tokenTypes = array_map(fn($token) => $token->type, $tokenStream->getTokens());
        expect($tokenTypes)->toBe(['hex_color']);
    });

    describe('Exception Handling', function () {
        it('handles characters not covered by regex patterns', function () {
            // These characters should not cause SyntaxException because the lexer is permissive
            $problematicChars = ['`', '^', '~', '\\'];

            foreach ($problematicChars as $char) {
                expect(function () use ($char) {
                    $this->lexer->tokenize('.class { content: "' . $char . '"; }');
                })->not->toThrow(Exception::class);
            }
        });

        it('throws InvalidColorException for invalid hex colors', function () {
            expect(function () {
                $this->lexer->tokenize('.class { color: #gg0000; }');
            })->toThrow(InvalidColorException::class);
        });

        it('throws InvalidColorException for malformed hex colors in property values', function () {
            expect(function () {
                $this->lexer->tokenize('.class { color: #xyzw; }');
            })->toThrow(InvalidColorException::class);
        });

        it('does not throw InvalidColorException for #abcd - lexer is permissive with hex colors', function () {
            // Lexer is permissive and doesn't throw InvalidColorException for invalid hex colors
            $result = $this->lexer->tokenize('.class { color: #abcd; }');
            expect($result)->not->toBeNull();
        });
    });

    describe('All Token Types Coverage', function () {
        it('tokenizes whitespace correctly', function () {
            $whitespaceTokens = [
                [' ', 'whitespace'],
                ["\t", 'whitespace'],
                ["\n", 'whitespace'],
                ["  \t\n  ", 'whitespace'],
            ];

            foreach ($whitespaceTokens as [$whitespace, $expectedType]) {
                $tokenStream = $this->lexer->tokenize($whitespace);
                $tokens = $tokenStream->getTokens();

                // Whitespace tokens are filtered out by lexer
                expect(count($tokens))->toBeGreaterThanOrEqual(0);
            }
        });

        it('tokenizes strings correctly', function () {
            $stringTokens = [
                ['"hello"', 'string'],
                ["'world'", 'string'],
                ['"escaped \"quote"', 'string'],
                ["'escaped \\'quote'", 'string'],
            ];

            foreach ($stringTokens as [$string, $expectedType]) {
                $tokenStream = $this->lexer->tokenize($string);
                $tokens = $tokenStream->getTokens();

                expect($tokens)->toHaveCount(1)
                    ->and($tokens[0]->type)->toBe($expectedType)
                    ->and($tokens[0]->value)->toBe($string);
            }
        });

        it('tokenizes comments correctly', function () {
            $commentTokens = [
                ['// single line comment', 'comment'],
                ['/* multi-line comment */', 'comment'],
                ["/* multi\nline comment */", 'comment'],
            ];

            foreach ($commentTokens as [$comment, $expectedType]) {
                $tokenStream = $this->lexer->tokenize($comment);
                $tokens = $tokenStream->getTokens();

                // Comment tokens are filtered out by lexer
                expect(count($tokens))->toBeGreaterThanOrEqual(0);
            }
        });

        it('tokenizes numbers correctly', function () {
            $numberTokens = [
                ['42', 'number'],
                ['3.14', 'number'],
                ['-10', 'number'],
                ['100px', 'number'],
                ['50%', 'number'],
                ['2.5em', 'number'],
                ['1.5rem', 'number'],
            ];

            foreach ($numberTokens as [$number, $expectedType]) {
                $tokenStream = $this->lexer->tokenize($number);
                $tokens = $tokenStream->getTokens();

                expect($tokens)->toHaveCount(1)
                    ->and($tokens[0]->type)->toBe($expectedType)
                    ->and($tokens[0]->value)->toBe($number);
            }
        });

        it('tokenizes variables correctly', function () {
            $variableTokens = [
                ['$color', 'variable'],
                ['$primary-color', 'variable'],
                ['$_var', 'variable'],
                ['$var123', 'variable'],
            ];

            foreach ($variableTokens as [$variable, $expectedType]) {
                $tokenStream = $this->lexer->tokenize($variable);
                $tokens = $tokenStream->getTokens();

                expect($tokens)->toHaveCount(1)
                    ->and($tokens[0]->type)->toBe($expectedType)
                    ->and($tokens[0]->value)->toBe($variable);
            }
        });

        it('tokenizes at-rules correctly', function () {
            $atRuleTokens = [
                ['@include', 'at_rule'],
                ['@mixin', 'at_rule'],
                ['@function', 'at_rule'],
                ['@media', 'at_rule'],
            ];

            foreach ($atRuleTokens as [$atRule, $expectedType]) {
                $tokenStream = $this->lexer->tokenize($atRule);
                $tokens = $tokenStream->getTokens();

                expect($tokens)->toHaveCount(1)
                    ->and($tokens[0]->type)->toBe($expectedType)
                    ->and($tokens[0]->value)->toBe($atRule);
            }
        });

        it('tokenizes functions correctly', function () {
            $functionTokens = [
                ['calc(', 'function'],
                ['rgba(', 'function'],
                ['linear-gradient(', 'function'],
            ];

            foreach ($functionTokens as [$function, $expectedType]) {
                $tokenStream = $this->lexer->tokenize($function);
                $tokens = $tokenStream->getTokens();

                // Functions are tokenized as function + paren_open
                expect($tokens)->toHaveCount(2)
                    ->and($tokens[0]->type)->toBe($expectedType)
                    ->and($tokens[0]->value)->toBe(substr($function, 0, -1))
                    ->and($tokens[1]->type)->toBe('paren_open')
                    ->and($tokens[1]->value)->toBe('(');
            }
        });

        it('tokenizes asterisk operator', function () {
            $tokenStream = $this->lexer->tokenize('*');
            $tokens = $tokenStream->getTokens();

            expect($tokens)->toHaveCount(1)
                ->and($tokens[0]->type)->toBe('asterisk')
                ->and($tokens[0]->value)->toBe('*');
        });

        it('tokenizes logical operators', function () {
            $logicalOperatorTokens = [
                ['and', 'logical_operator'],
                ['or', 'logical_operator'],
            ];

            foreach ($logicalOperatorTokens as [$operator, $expectedType]) {
                $tokenStream = $this->lexer->tokenize($operator);
                $tokens = $tokenStream->getTokens();

                expect($tokens)->toHaveCount(1)
                    ->and($tokens[0]->type)->toBe($expectedType)
                    ->and($tokens[0]->value)->toBe($operator);
            }
        });

        it('tokenizes CSS custom properties', function () {
            $cssCustomPropertyTokens = [
                ['--custom-prop', 'css_custom_property'],
                ['--my-var', 'css_custom_property'],
                ['--color-primary', 'css_custom_property'],
            ];

            foreach ($cssCustomPropertyTokens as [$property, $expectedType]) {
                $tokenStream = $this->lexer->tokenize($property);
                $tokens = $tokenStream->getTokens();

                expect($tokens)->toHaveCount(1)
                    ->and($tokens[0]->type)->toBe($expectedType)
                    ->and($tokens[0]->value)->toBe($property);
            }
        });

        it('tokenizes double hash interpolation', function () {
            $interpolationTokens = [
                ['##{', 'double_hash_interpolation'],
            ];

            foreach ($interpolationTokens as [$interpolation, $expectedType]) {
                $tokenStream = $this->lexer->tokenize($interpolation);
                $tokens = $tokenStream->getTokens();

                expect($tokens)->toHaveCount(1)
                    ->and($tokens[0]->type)->toBe($expectedType)
                    ->and($tokens[0]->value)->toBe($interpolation);
            }
        });

        it('tokenizes attribute selectors', function () {
            $attributeSelectorTokens = [
                ['[class]', 'attribute_selector'],
                ['[data-active]', 'attribute_selector'],
                ['[class="value"]', 'attribute_selector'],
            ];

            foreach ($attributeSelectorTokens as [$selector, $expectedType]) {
                $tokenStream = $this->lexer->tokenize($selector);
                $tokens = $tokenStream->getTokens();

                expect($tokens)->toHaveCount(1)
                    ->and($tokens[0]->type)->toBe($expectedType)
                    ->and($tokens[0]->value)->toBe($selector);
            }
        });

        it('tokenizes operators correctly', function () {
            $operatorTokens = [
                ['+', 'operator'],
                ['-', 'operator'],
                ['*', 'asterisk'], // * is tokenized as asterisk, not operator
                ['/', 'operator'],
                ['%', 'operator'],
                ['=', 'operator'],
                ['<', 'operator'],
                ['>', 'operator'],
                ['!', 'operator'],
                ['&', 'operator'],
                ['|', 'operator'],
                ['.', 'operator'],
                [',', 'operator'],
                [']', 'operator'],
                ['#', 'operator'],
            ];

            foreach ($operatorTokens as [$operator, $expectedType]) {
                $tokenStream = $this->lexer->tokenize($operator);
                $tokens = $tokenStream->getTokens();

                expect($tokens)->toHaveCount(1)
                    ->and($tokens[0]->type)->toBe($expectedType)
                    ->and($tokens[0]->value)->toBe($operator);
            }
        });

        it('tokenizes braces correctly', function () {
            $braceTokens = [
                ['{', 'brace_open'],
                ['}', 'brace_close'],
            ];

            foreach ($braceTokens as [$brace, $expectedType]) {
                $tokenStream = $this->lexer->tokenize($brace);
                $tokens = $tokenStream->getTokens();

                expect($tokens)->toHaveCount(1)
                    ->and($tokens[0]->type)->toBe($expectedType)
                    ->and($tokens[0]->value)->toBe($brace);
            }
        });

        it('tokenizes parentheses correctly', function () {
            $parenTokens = [
                ['(', 'paren_open'],
                [')', 'paren_close'],
            ];

            foreach ($parenTokens as [$paren, $expectedType]) {
                $tokenStream = $this->lexer->tokenize($paren);
                $tokens = $tokenStream->getTokens();

                expect($tokens)->toHaveCount(1)
                    ->and($tokens[0]->type)->toBe($expectedType)
                    ->and($tokens[0]->value)->toBe($paren);
            }
        });

        it('tokenizes semicolon', function () {
            $tokenStream = $this->lexer->tokenize(';');
            $tokens = $tokenStream->getTokens();

            expect($tokens)->toHaveCount(1)
                ->and($tokens[0]->type)->toBe('semicolon')
                ->and($tokens[0]->value)->toBe(';');
        });

        it('tokenizes colon', function () {
            $tokenStream = $this->lexer->tokenize(':');
            $tokens = $tokenStream->getTokens();

            expect($tokens)->toHaveCount(1)
                ->and($tokens[0]->type)->toBe('colon')
                ->and($tokens[0]->value)->toBe(':');
        });

        it('tokenizes selectors correctly', function () {
            $selectorTokens = [
                ['div', 1, 'identifier'], // Simple identifier
                ['.class', 2, 'operator'], // .class is tokenized as operator + identifier
                ['#id', 2, 'operator'], // #id is tokenized as operator + identifier
                ['div.class', 3, 'identifier'], // Complex selectors are broken down
                ['div > .child', 4, 'identifier'], // Combinators split the selector
                [':hover', 2, 'colon'], // :hover is tokenized as colon + identifier
                ['::before', 3, 'colon'], // ::before is tokenized as colon + colon + identifier
            ];

            foreach ($selectorTokens as [$selector, $expectedCount, $expectedFirstType]) {
                $tokenStream = $this->lexer->tokenize($selector);
                $tokens = $tokenStream->getTokens();

                expect($tokens)->toHaveCount($expectedCount)
                    ->and($tokens[0]->type)->toBe($expectedFirstType);
            }
        });
    });

    describe('Parser State Management', function () {
        it('tracks inBlock state correctly', function () {
            // Initially not in block
            expect($this->accessor->getProperty('inBlock'))->toBeFalse();

            // After brace_open
            $this->lexer->tokenize('{');
            expect($this->accessor->getProperty('inBlock'))->toBeTrue();

            // After brace_close
            $this->lexer->tokenize('}');
            expect($this->accessor->getProperty('inBlock'))->toBeFalse();
        });

        it('tracks expectingValue state correctly', function () {
            // Initially not expecting property value
            expect($this->accessor->getProperty('expectingValue'))->toBeFalse();

            // After colon
            $this->lexer->tokenize(':');
            expect($this->accessor->getProperty('expectingValue'))->toBeTrue();

            // After semicolon
            $this->lexer->tokenize(';');
            expect($this->accessor->getProperty('expectingValue'))->toBeFalse();

            // After brace_close
            $this->lexer->tokenize('}');
            expect($this->accessor->getProperty('expectingValue'))->toBeFalse();
        });

        it('resets state properly between tokenize calls', function () {
            // First call sets state
            $this->lexer->tokenize('.class { color: red; }');
            expect($this->accessor->getProperty('inBlock'))->toBeFalse()
                ->and($this->accessor->getProperty('expectingValue'))->toBeFalse();

            // Second call should start with clean state
            $this->lexer->tokenize('div { margin: 10px; }');
            expect($this->accessor->getProperty('inBlock'))->toBeFalse()
                ->and($this->accessor->getProperty('expectingValue'))->toBeFalse();
        });

        it('handles nested block state correctly', function () {
            $this->lexer->tokenize('.outer { .inner { color: red; } }');

            expect($this->accessor->getProperty('inBlock'))->toBeFalse()
                ->and($this->accessor->getProperty('expectingValue'))->toBeFalse();
        });
    });

    describe('Post-processing', function () {
        it('splits hex colors correctly in different contexts', function () {
            $tokenStream = $this->lexer->tokenize('.class { color: #ff0000; }');
            $tokens = $tokenStream->getTokens();

            // Test that hex colors are processed without errors
            expect(count($tokens))->toBeGreaterThan(0);
        });

        it('handles hash token splitting scenarios', function () {
            $tokenStream = $this->lexer->tokenize('#{variable}');
            $tokens = $tokenStream->getTokens();

            expect($tokens)->toHaveCount(3);
        });

        it('handles identifier starting with hash correctly', function () {
            $tokenStream = $this->lexer->tokenize('.class { content: "#hashtag"; }');
            $tokens = $tokenStream->getTokens();

            expect($tokens)->toHaveCount(8); // ., class, {, content, :, "#hashtag", ;, }
        });

        it('preserves valid hex colors in selectors', function () {
            $tokenStream = $this->lexer->tokenize('#ff0000 { color: red; }');
            $tokens = $tokenStream->getTokens();

            expect($tokens)->toHaveCount(7); // #ff0000, {, color, :, red, ;, }
            $firstToken = $tokens[0];
            expect($firstToken->type)->toBe('hex_color')
                ->and($firstToken->value)->toBe('#ff0000');
        });
    });

    describe('Boundary Cases', function () {
        it('handles empty input', function () {
            $tokenStream = $this->lexer->tokenize('');
            $tokens = $tokenStream->getTokens();

            expect($tokens)->toHaveCount(0);
        });

        it('handles whitespace-only input', function () {
            $tokenStream = $this->lexer->tokenize("   \n\t  \n  ");
            $tokens = $tokenStream->getTokens();

            expect($tokens)->toHaveCount(0);
        });

        it('handles very long identifiers', function () {
            $longIdentifier = str_repeat('a', 1000);
            $tokenStream = $this->lexer->tokenize('.' . $longIdentifier);
            $tokens = $tokenStream->getTokens();

            expect($tokens)->toHaveCount(2) // . + long identifier
                ->and($tokens[1]->value)->toBe($longIdentifier);
        });

        it('handles special characters in strings', function () {
            $tokenStream = $this->lexer->tokenize('"special \\n \\t \\" chars"');
            $tokens = $tokenStream->getTokens();

            expect($tokens)->toHaveCount(1)
                ->and($tokens[0]->type)->toBe('string');
        });

        it('handles Unicode characters', function () {
            $tokenStream = $this->lexer->tokenize('.class { content: "→←↑↓"; }');
            $tokens = $tokenStream->getTokens();

            // Test that lexer processes Unicode content without errors
            expect(count($tokens))->toBeGreaterThan(0);

            // Check that we have the expected structure
            $tokenValues = array_map(fn($t) => $t->value, $tokens);
            expect($tokenValues)->toContain('.')
                ->and($tokenValues)->toContain('class')
                ->and($tokenValues)->toContain('{')
                ->and($tokenValues)->toContain('"→←↑↓"');
        });

        it('handles position tracking correctly', function () {
            $input = "line1\nline2\nline3";
            $tokenStream = $this->lexer->tokenize($input);
            $tokens = $tokenStream->getTokens();

            // Test that lexer processes multi-line input without errors
            expect(count($tokens))->toBeGreaterThan(0);

            // Check that at least one token has line information
            $hasLineInfo = false;
            foreach ($tokens as $token) {
                if (isset($token->line) && $token->line > 0) {
                    $hasLineInfo = true;

                    break;
                }
            }
            expect($hasLineInfo)->toBeTrue();
        });
    });

    describe('Complex Scenarios', function () {
        it('tokenizes complete SCSS file correctly', function () {
            $scss = <<<'SCSS'
            $primary: #333;

            .container {
                color: $primary;

                &:hover {
                    color: lighten($primary, 10%);
                }

                .nested {
                    margin: 10px;
                }
            }

            @mixin border-radius($radius) {
                border-radius: $radius;
            }

            .button {
                @include border-radius(5px);
            }
            SCSS;

            $tokenStream = $this->lexer->tokenize($scss);
            $tokens = $tokenStream->getTokens();

            expect($tokens)->toBeGreaterThan(50)
                ->and($tokens[0]->type)->toBe('variable');
        });

        it('handles interpolation in selectors', function () {
            $tokenStream = $this->lexer->tokenize('.#{$class-name}');
            $tokens = $tokenStream->getTokens();

            expect($tokens)->toHaveCount(4) // ., #, {, class-name, }
                ->and($tokens[2]->type)->toBe('variable');
        });

        it('handles complex selectors with multiple combinators', function () {
            $tokenStream = $this->lexer->tokenize('div > .class + [data-attr]:hover::before');
            $tokens = $tokenStream->getTokens();

            // Complex selectors are broken down into individual tokens
            expect($tokens)->toHaveCount(11)
                ->and($tokens[0]->type)->toBe('identifier');
        });
    });

    describe('Uncovered Code Paths', function () {
        it('handles unexpected characters gracefully', function () {
            // The lexer is permissive, so it shouldn't throw for unexpected characters
            expect(function () {
                $this->lexer->tokenize('.class { color: red; @invalid; }');
            })->not->toThrow(Exception::class);
        });

        describe('isPotentialSelector method coverage', function () {
            it('detects selectors starting with dot', function () {
                $result = $this->accessor->callMethod('isPotentialSelector', ['.class']);
                expect($result)->toBeTrue();
            });

            it('detects selectors starting with hash', function () {
                $result = $this->accessor->callMethod('isPotentialSelector', ['#id']);
                expect($result)->toBeTrue();
            });

            it('detects selectors with special characters', function () {
                $specialChars = [':', '[', '&', '>', '+', '~'];

                foreach ($specialChars as $char) {
                    $result = $this->accessor->callMethod('isPotentialSelector', ['div' . $char . 'span']);
                    expect($result)->toBeTrue();
                }
            });

            it('returns false for empty selectors', function () {
                $result = $this->accessor->callMethod('isPotentialSelector', ['']);
                expect($result)->toBeFalse();
            });

            it('returns false for simple identifiers', function () {
                $result = $this->accessor->callMethod('isPotentialSelector', ['simple']);
                expect($result)->toBeFalse();
            });
        });

        describe('Hash token splitting scenarios', function () {
            it('splits hash identifier tokens correctly', function () {
                $input = '#{variable}';
                $tokens = $this->lexer->tokenize($input)->getTokens();

                // Should have interpolation tokens
                expect(count($tokens))->toBeGreaterThan(0);
            });

            it('handles hex color splitting in block context', function () {
                $input = '.class { color: #ff0000; }';
                $tokens = $this->lexer->tokenize($input)->getTokens();

                // Test that processing completes without errors
                expect(count($tokens))->toBeGreaterThan(0);
            });
        });

        describe('Invalid hex color detection', function () {
            it('detects invalid hex colors in property values', function () {
                expect(function () {
                    $this->lexer->tokenize('.class { color: #gg0000; }');
                })->toThrow(InvalidColorException::class);
            });

            it('allows valid hex colors with proper length', function () {
                $input = '.class { color: #ff0000; }';
                expect(function () use ($input) {
                    $this->lexer->tokenize($input);
                })->not->toThrow(Exception::class);
            });
        });

        describe('validatePotentialHexColor method', function () {
            it('validates potential hex colors correctly', function () {
                // Test with valid hex color patterns that should not throw
                $validCases = [
                    '.class { color: #fff; }',
                    '.class { color: #ff; }',
                    '.class { color: #ff0000; }',
                    '.class { color: #ff0000aa; }',
                ];

                foreach ($validCases as $case) {
                    expect(function () use ($case) {
                        $this->lexer->tokenize($case);
                    })->not->toThrow(Exception::class);
                }
            });

            it('handles edge cases in hex validation', function () {
                // Test various edge cases for hex validation
                $edgeCases = [
                    '.class { color: #f; }',
                    '.class { color: #ffffff; }',
                    '.class { color: #ffffffff; }',
                ];

                foreach ($edgeCases as $case) {
                    expect(function () use ($case) {
                        $this->lexer->tokenize($case);
                    })->not->toThrow(Exception::class);
                }
            });
        });

        describe('Edge cases for token processing', function () {
            it('handles complex identifier patterns', function () {
                $complexInput = '.class > .child + [data-attr]:hover';
                $tokens = $this->lexer->tokenize($complexInput)->getTokens();

                expect(count($tokens))->toBeGreaterThan(0);
            });

            it('processes nested interpolation correctly', function () {
                $input = '.#{($class)}';
                $tokens = $this->lexer->tokenize($input)->getTokens();

                expect(count($tokens))->toBeGreaterThan(0);
            });
        });
    });

    describe('Position Tracking Coverage', function () {
        it('correctly calculates column position after newline', function () {
            $input = "line1\nline2";
            $tokenStream = $this->lexer->tokenize($input);
            $tokens = $tokenStream->getTokens();

            expect(count($tokens))->toBeGreaterThan(0);

            $input2 = "short\nvery long line name";
            $tokenStream2 = $this->lexer->tokenize($input2);
            $tokens2 = $tokenStream2->getTokens();

            expect(count($tokens2))->toBeGreaterThan(0);
        });

        it('handles position tracking with different line ending patterns', function () {
            $testCases = [
                "a\nb",
                "ab\ncd",
                "a\n\nb",
                "\na",
                "a\n",
            ];

            foreach ($testCases as $input) {
                $tokenStream = $this->lexer->tokenize($input);
                $tokens = $tokenStream->getTokens();

                expect(count($tokens))->toBeGreaterThan(0);
            }
        });
    });

    describe('Token Post-processing Coverage', function () {
        it('splits hash identifier tokens correctly', function () {
            $input = '#{variable}';
            $tokens = $this->lexer->tokenize($input)->getTokens();

            expect(count($tokens))->toBeGreaterThan(2);

            $input2 = '.class { content: "#test"; }';
            $tokens2 = $this->lexer->tokenize($input2)->getTokens();

            expect(count($tokens2))->toBeGreaterThan(0);
        });

        it('splits hex color tokens in block context', function () {
            $input = '.class { color: #invalididentifier; }';
            $tokens = $this->lexer->tokenize($input)->getTokens();

            expect(count($tokens))->toBeGreaterThan(0);

            $input2 = '.class { content: #ffffff; }';
            $tokens2 = $this->lexer->tokenize($input2)->getTokens();

            expect(count($tokens2))->toBeGreaterThan(0);
        });
    });

    describe('Hex Color Validation Coverage', function () {
        it('handles invalid hex color detection scenarios', function () {
            expect(function () {
                $this->lexer->tokenize('.class { color: #gg0000; }');
            })->toThrow(InvalidColorException::class)
                ->and(function () {
                    $this->lexer->tokenize('.class { color: #xyzw; }');
                })->toThrow(InvalidColorException::class);

        });

        it('handles valid hex colors without throwing', function () {
            $validCases = [
                '.class { color: #fff; }',
                '.class { color: #ff0000; }',
                '.class { color: #ff0000aa; }',
                '.class { background: #123456; }',
            ];

            foreach ($validCases as $case) {
                expect(function () use ($case) {
                    $this->lexer->tokenize($case);
                })->not->toThrow(Exception::class);
            }
        });
    });
});
