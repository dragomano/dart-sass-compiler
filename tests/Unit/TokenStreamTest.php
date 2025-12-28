<?php

declare(strict_types=1);

use DartSass\Exceptions\SyntaxException;
use DartSass\Parsers\Lexer;
use DartSass\Parsers\Token;
use DartSass\Parsers\TokenStream;

describe('TokenStream', function () {
    beforeEach(function () {
        $this->lexer = new Lexer();
        $this->tokenStream = $this->lexer->tokenize('.class > .child { color: red; }');
    });

    it('provides access to current token', function () {
        expect($this->tokenStream->current()->type)->toBe('operator')
            ->and($this->tokenStream->current()->value)->toBe('.');
    });

    it('advances to next token', function () {
        $this->tokenStream->advance();
        expect($this->tokenStream->current()->type)->toBe('identifier')
            ->and($this->tokenStream->current()->value)->toBe('class');
    });

    it('advances by multiple positions', function () {
        $this->tokenStream->advance(3);
        expect($this->tokenStream->current()->type)->toBe('operator')
            ->and($this->tokenStream->current()->value)->toBe('.');
    });

    it('provides access to tokens by index', function () {
        expect($this->tokenStream->getToken(0)->value)->toBe('.')
            ->and($this->tokenStream->getToken(1)->value)->toBe('class')
            ->and($this->tokenStream->getToken(2)->value)->toBe('>')
            ->and($this->tokenStream->getToken(3)->value)->toBe('.')
            ->and($this->tokenStream->getToken(4)->value)->toBe('child');
    });

    it('returns null for out of bounds index', function () {
        expect($this->tokenStream->getToken(100))->toBeNull();
    });

    it('gets current position', function () {
        expect($this->tokenStream->getPosition())->toBe(0);

        $this->tokenStream->advance(2);
        expect($this->tokenStream->getPosition())->toBe(2);
    });

    it('sets position', function () {
        $this->tokenStream->setPosition(4);
        expect($this->tokenStream->getPosition())->toBe(4)
            ->and($this->tokenStream->current()->value)->toBe('child');
    });

    it('checks if at end of stream', function () {
        expect($this->tokenStream->isEnd())->toBeFalse();

        $this->tokenStream->setPosition(15); // beyond actual tokens (11 tokens total)
        expect($this->tokenStream->isEnd())->toBeTrue();
    });

    it('counts tokens', function () {
        expect($this->tokenStream->count())->toBe(11); // .class > .child { color: red; }
    });

    it('matches token type', function () {
        expect($this->tokenStream->matches('operator'))->toBeTrue()
            ->and($this->tokenStream->matches('identifier'))->toBeFalse();
    });

    it('matches any of multiple token types', function () {
        expect($this->tokenStream->matchesAny('identifier', 'brace_open'))->toBeFalse();

        $this->tokenStream->advance();
        expect($this->tokenStream->matchesAny('identifier', 'brace_open'))->toBeTrue();
    });

    it('peeks at next token without advancing', function () {
        $peeked = $this->tokenStream->peek();
        expect($peeked->value)->toBe('class')
            ->and($this->tokenStream->current()->value)->toBe('.');
    });

    it('peeks with offset', function () {
        $peeked = $this->tokenStream->peek(2);
        expect($peeked->value)->toBe('>')
            ->and($this->tokenStream->current()->value)->toBe('.');
    });

    it('peeks at token type', function () {
        expect($this->tokenStream->peekType())->toBe('identifier')
            ->and($this->tokenStream->peekType(2))->toBe('operator');
    });

    it('peeks at token value', function () {
        expect($this->tokenStream->peekValue())->toBe('class')
            ->and($this->tokenStream->peekValue(2))->toBe('>');
    });

    it('consumes expected token type', function () {
        $consumed = $this->tokenStream->consume('operator');
        expect($consumed->value)->toBe('.')
            ->and($this->tokenStream->current()->value)->toBe('class');
    });

    it('consumes token if it matches type', function () {
        $consumed = $this->tokenStream->consumeIf('operator');
        expect($consumed->value)->toBe('.')
            ->and($this->tokenStream->current()->value)->toBe('class');

        $consumed = $this->tokenStream->consumeIf('brace_open');
        expect($consumed)->toBeNull()
            ->and($this->tokenStream->current()->value)->toBe('class');
    });

    it('expects any of multiple token types', function () {
        $expected = $this->tokenStream->expectAny('operator', 'identifier');
        expect($expected->value)->toBe('.');
    });

    it('throws exception when expecting token type that does not match', function () {
        expect(fn() => $this->tokenStream->expectAny('brace_open', 'colon'))
            ->toThrow(SyntaxException::class);
    });

    it('skips tokens of specified types', function () {
        $this->tokenStream->skipTokens('operator', 'identifier');
        expect($this->tokenStream->current()->value)->toBe('{');
    });

    it('skips whitespace tokens', function () {
        $tokenStream = $this->lexer->tokenize('.class    >   .child');

        expect($tokenStream->count())->toBe(5)
            ->and($tokenStream->getTokens()[2]->value)->toBe('>');
    });

    it('returns all tokens as array', function () {
        $tokens = $this->tokenStream->getTokens();
        expect($tokens)->toHaveCount(11)
            ->and($tokens[0]->value)->toBe('.')
            ->and($tokens[1]->value)->toBe('class')
            ->and($tokens[2]->value)->toBe('>')
            ->and($tokens[3]->value)->toBe('.')
            ->and($tokens[4]->value)->toBe('child');
    });

    it('handles empty token stream', function () {
        $emptyStream = $this->lexer->tokenize('');

        expect($emptyStream->count())->toBe(0)
            ->and($emptyStream->isEnd())->toBeTrue()
            ->and($emptyStream->current())->toBeNull();
    });

    describe('Exception Testing', function () {
        it('throws SyntaxException when consuming beyond end of stream', function () {
            $this->tokenStream->setPosition(20); // Beyond token count (11 tokens)

            expect(fn() => $this->tokenStream->consume('operator'))
                ->toThrow(SyntaxException::class, 'Expected operator, but reached end of input');
        });

        it('throws SyntaxException when consuming wrong token type', function () {
            expect(fn() => $this->tokenStream->consume('identifier'))
                ->toThrow(SyntaxException::class, 'Expected identifier, got operator');
        });

        it('throws SyntaxException when expectAny() reaches end of stream', function () {
            $this->tokenStream->setPosition(20); // Beyond token count

            expect(fn() => $this->tokenStream->expectAny('operator', 'identifier'))
                ->toThrow(SyntaxException::class, 'Expected one of [operator, identifier], but reached end of input');
        });

        it('throws SyntaxException when expectAny() gets wrong token type', function () {
            expect(fn() => $this->tokenStream->expectAny('brace_open', 'colon'))
                ->toThrow(SyntaxException::class, 'Expected one of [brace_open, colon], got operator');
        });
    });

    describe('Boundary Conditions', function () {
        it('handles negative advance amount', function () {
            $this->tokenStream->advance(5);
            $this->tokenStream->advance(-2);

            expect($this->tokenStream->getPosition())->toBe(3);
        });

        it('handles advance with very large negative amount', function () {
            $this->tokenStream->advance(-1000);

            expect($this->tokenStream->getPosition())->toBe(-1000)
                ->and($this->tokenStream->current())->toBeNull();
        });

        it('peeks with negative offset', function () {
            $peeked = $this->tokenStream->peek(-1);
            expect($peeked)->toBeNull();

            $this->tokenStream->advance(2);
            $peeked = $this->tokenStream->peek(-1);
            expect($peeked->value)->toBe('class'); // Position 2 - 1 = 1, which is 'class'
        });

        it('handles setPosition with negative values', function () {
            $this->tokenStream->setPosition(-5);
            expect($this->tokenStream->getPosition())->toBe(-5)
                ->and($this->tokenStream->current())->toBeNull();
        });

        it('handles setPosition with very large values', function () {
            $this->tokenStream->setPosition(1000);
            expect($this->tokenStream->getPosition())->toBe(1000)
                ->and($this->tokenStream->isEnd())->toBeTrue()
                ->and($this->tokenStream->current())->toBeNull();
        });

        it('handles peek with very large offset', function () {
            $peeked = $this->tokenStream->peek(1000);
            expect($peeked)->toBeNull();
        });
    });

    describe('Edge Cases', function () {
        it('handles Unicode whitespace in skipWhitespace', function () {
            $tokenStream = $this->lexer->tokenize('.class  >  .child'); // Uses Unicode spaces

            expect($tokenStream->count())->toBe(17)
                ->and($tokenStream->current()->value)->toBe('.'); // Unicode spaces are treated as separate tokens

            $tokenStream->skipWhitespace();
            expect($tokenStream->current()->value)->toBe('.'); // Unicode whitespace not skipped by skipWhitespace
        });

        it('handles mixed whitespace types', function () {
            $tokenStream = $this->lexer->tokenize(".class \t\n\r > .child");

            expect($tokenStream->count())->toBe(5); // .class, whitespace, whitespace, whitespace, >, whitespace, .child
            $tokenStream->skipWhitespace();
            expect($tokenStream->current()->value)->toBe('.'); // skipWhitespace should skip the whitespace tokens
        });

        it('handles empty skipTokens call', function () {
            $position = $this->tokenStream->getPosition();
            $this->tokenStream->skipTokens();
            expect($this->tokenStream->getPosition())->toBe($position);
        });

        it('handles skipTokens with non-existent token types', function () {
            $position = $this->tokenStream->getPosition();
            $this->tokenStream->skipTokens('nonexistent_type');
            expect($this->tokenStream->getPosition())->toBe($position);
        });
    });

    describe('Integration Testing', function () {
        it('maintains consistent state after multiple operations', function () {
            $this->tokenStream->advance(2);
            $this->tokenStream->setPosition(1);
            $this->tokenStream->advance();

            expect($this->tokenStream->getPosition())->toBe(2)
                ->and($this->tokenStream->current()->value)->toBe('>');
        });

        it('works correctly with skipTokens and skipWhitespace combination', function () {
            $tokenStream = $this->lexer->tokenize('.class > .child { color : red ; }');

            $tokenStream->skipTokens('operator'); // Skip '.'
            $tokenStream->skipWhitespace(); // Skip spaces
            expect($tokenStream->current()->value)->toBe('class');

            $tokenStream->skipTokens('identifier'); // Skip 'class'
            $tokenStream->skipWhitespace(); // Skip spaces
            expect($tokenStream->current()->value)->toBe('>');
        });

        it('handles peek and consume combination correctly', function () {
            $peekedToken = $this->tokenStream->peek(2);
            $currentToken = $this->tokenStream->current();

            expect($peekedToken->value)->toBe('>')
                ->and($currentToken->value)->toBe('.');

            $consumedToken = $this->tokenStream->consume('operator');
            expect($consumedToken->value)->toBe('.')
                ->and($this->tokenStream->current()->value)->toBe('class');
        });

        it('validates state consistency across method calls', function () {
            $originalCount = $this->tokenStream->count();
            $originalPosition = $this->tokenStream->getPosition();

            $this->tokenStream->advance(3);
            $this->tokenStream->setPosition(1);
            $this->tokenStream->advance(-1);

            expect($this->tokenStream->count())->toBe($originalCount)
                ->and($this->tokenStream->getPosition())->toBe($originalPosition);
        });
    });

    describe('Performance and Caching', function () {
        it('validates caching behavior for current', function () {
            // First call should cache the token
            $token1 = $this->tokenStream->current();

            // Second call should return cached token
            $token2 = $this->tokenStream->current();

            expect($token1)->toBe($token2);

            // After advance, cache should be invalidated
            $this->tokenStream->advance();
            $token3 = $this->tokenStream->current();

            expect($token3)->not->toBe($token1);
        });

        it('validates cache invalidation on set position', function () {
            $token1 = $this->tokenStream->current();
            $this->tokenStream->setPosition(2);
            $token2 = $this->tokenStream->current();

            expect($token1)->not->toBe($token2);

            $this->tokenStream->setPosition(0);
            $token3 = $this->tokenStream->current();

            expect($token3)->toBe($token1);
        });

        it('handles large number of operations', function () {
            $startTime = microtime(true);

            // Perform many operations
            for ($i = 0; $i < 1000; $i++) {
                $this->tokenStream->current();
                $this->tokenStream->peek();
                if (! $this->tokenStream->isEnd()) {
                    $this->tokenStream->advance();
                }
            }

            $endTime = microtime(true);
            $duration = $endTime - $startTime;

            // Should complete within reasonable time (less than 1 second)
            expect($duration)->toBeLessThan(1.0);
        });
    });

    describe('Advanced Method Combinations', function () {
        it('handles complex token consumption workflow', function () {
            $tokenStream = $this->lexer->tokenize('.class > .child { color: red; }');

            // Peek ahead
            expect($tokenStream->peekType(2))->toBe('operator');

            // Consume expected token
            $consumed = $tokenStream->consume('operator');
            expect($consumed->value)->toBe('.');

            // Skip tokens of specific types
            $tokenStream->skipTokens('identifier');
            expect($tokenStream->current()->value)->toBe('>');

            // Use consumeIf conditionally
            $maybeConsumed = $tokenStream->consumeIf('operator');
            expect($maybeConsumed->value)->toBe('>');

            // Expect any of remaining types
            $expected = $tokenStream->expectAny('operator', 'identifier');
            expect($expected->value)->toBe('.');
        });

        it('validates peek value and peek type consistency', function () {
            $peekedToken = $this->tokenStream->peek(2);
            $peekedType = $this->tokenStream->peekType(2);
            $peekedValue = $this->tokenStream->peekValue(2);

            expect($peekedToken->type)->toBe($peekedType)
                ->and($peekedToken->value)->toBe($peekedValue);
        });
    });

    describe('skipWhitespace() method', function () {
        it('skips whitespace tokens when they exist', function () {
            $tokens = [
                new Token('operator', '.', 1, 1),
                new Token('whitespace', ' ', 1, 2),
                new Token('whitespace', ' ', 1, 3),
                new Token('identifier', 'class', 1, 4),
            ];

            $stream = new TokenStream($tokens);

            expect($stream->current()->type)->toBe('operator')
                ->and($stream->current()->value)->toBe('.');

            $stream->advance();
            expect($stream->current()->type)->toBe('whitespace');

            $stream->skipWhitespace();

            expect($stream->current()->type)->toBe('identifier')
                ->and($stream->current()->value)->toBe('class');
        });

        it('does nothing when no whitespace tokens exist', function () {
            $tokens = [
                new Token('operator', '.', 1, 1),
                new Token('identifier', 'class', 1, 2),
            ];

            $stream = new TokenStream($tokens);

            $originalPosition = $stream->getPosition();
            $stream->skipWhitespace();

            expect($stream->getPosition())->toBe($originalPosition);
        });

        it('handles end of stream gracefully', function () {
            $tokens = [
                new Token('operator', '.', 1, 1),
            ];

            $stream = new TokenStream($tokens);
            $stream->setPosition(1);

            expect($stream->current())->toBeNull();

            $stream->skipWhitespace();
            expect($stream->current())->toBeNull();
        });
    });
});
