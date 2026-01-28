<?php

declare(strict_types=1);

use DartSass\Exceptions\SyntaxException;
use DartSass\Parsers\Nodes\UseNode;
use DartSass\Parsers\Rules\UseRuleParser;
use DartSass\Parsers\Tokens\Lexer;
use Tests\ReflectionAccessor;

describe('UseRuleParser', function () {
    beforeEach(function () {
        $this->lexer = new Lexer();

        $this->createParser = function (string $content) {
            $tokenStream = $this->lexer->tokenize($content);

            return new UseRuleParser($tokenStream);
        };
    });

    it('parses basic use rule with string path', function () {
        $parser = ($this->createParser)('@use "styles";');

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(UseNode::class)
            ->and($result->path)->toBe('styles')
            ->and($result->namespace)->toBe('styles');
    });

    it('parses use rule with single quotes', function () {
        $parser = ($this->createParser)("@use 'utilities';");

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(UseNode::class)
            ->and($result->path)->toBe('utilities')
            ->and($result->namespace)->toBe('utilities');
    });

    it('parses use rule with explicit namespace', function () {
        $parser = ($this->createParser)('@use "components" as cmp;');

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(UseNode::class)
            ->and($result->namespace)->toBe('cmp');
    });

    it('parses use rule with asterisk namespace and path without quotes', function () {
        $parser = ($this->createParser)('@use utils/helpers as *;');

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(UseNode::class)
            ->and($result->path)->toBe('utils/helpers')
            ->and($result->namespace)->toBe('*');
    });

    it('parses use rule with custom namespace and path without quotes', function () {
        $parser = ($this->createParser)('@use utils/helpers as myUtils;');

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(UseNode::class)
            ->and($result->path)->toBe('utils/helpers')
            ->and($result->namespace)->toBe('myUtils');
    });

    it('parses use rule with path without quotes', function () {
        $parser = ($this->createParser)('@use styles/base;');

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(UseNode::class)
            ->and($result->path)->toBe('styles/base')
            ->and($result->namespace)->toBe('base');
    });

    it('parses use rule with complex path', function () {
        $parser = ($this->createParser)('@use "../shared/components/button" as btn;');

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(UseNode::class)
            ->and($result->path)->toBe('../shared/components/button')
            ->and($result->namespace)->toBe('btn');
    });

    it('parses use rule with underscore prefix in filename', function () {
        $parser = ($this->createParser)('@use "_variables";');

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(UseNode::class)
            ->and($result->namespace)->toBe('variables');
    });

    it('throws exception when not @use rule', function () {
        $parser = ($this->createParser)('@import "module";');

        expect(fn() => $parser->parse())->toThrow(
            SyntaxException::class,
            'Expected @use rule, got @import'
        );
    });

    it('throws exception when semicolon is missing', function () {
        $parser = ($this->createParser)('@use "module"');

        expect(fn() => $parser->parse())->toThrow(SyntaxException::class);
    });

    it('handles path with file extension', function () {
        $parser = ($this->createParser)('@use "theme.scss";');

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(UseNode::class)
            ->and($result->namespace)->toBe('theme');
    });

    it('handles nested path structure', function () {
        $parser = ($this->createParser)('@use "abstracts/functions/math" as math;');

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(UseNode::class)
            ->and($result->path)->toBe('abstracts/functions/math')
            ->and($result->namespace)->toBe('math');
    });

    it('validates default namespace generation with reflection', function () {
        $parser = ($this->createParser)('@use "core/mixins";');

        $accessor = new ReflectionAccessor($parser);
        $result   = $accessor->callMethod('parse');

        expect($result)->toBeInstanceOf(UseNode::class)
            ->and($result->namespace)->toBe('mixins');
    });

    it('handles path with dots and hyphens', function () {
        $parser = ($this->createParser)('@use "./shared/layout/grid-system";');

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(UseNode::class)
            ->and($result->path)->toBe('./shared/layout/grid-system')
            ->and($result->namespace)->toBe('grid-system');
    });

    it('handles use rule with only path and auto namespace', function () {
        $parser = ($this->createParser)('@use abstracts/colors;');

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(UseNode::class)
            ->and($result->namespace)->toBe('colors');
    });
});
