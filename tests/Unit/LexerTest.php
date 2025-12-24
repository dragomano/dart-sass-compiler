<?php

declare(strict_types=1);

use DartSass\Parsers\Lexer;

describe('Lexer', function () {
    beforeEach(function () {
        $this->lexer = new Lexer();
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
});
