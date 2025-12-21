<?php declare(strict_types=1);

use DartSass\Exceptions\SyntaxException;
use DartSass\Parsers\Lexer;

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
});
