<?php

declare(strict_types=1);

use DartSass\Exceptions\SyntaxException;
use DartSass\Parsers\Nodes\ForwardNode;
use DartSass\Parsers\Rules\ForwardRuleParser;
use DartSass\Parsers\Tokens\Lexer;
use Tests\ReflectionAccessor;

describe('ForwardRuleParser', function () {
    beforeEach(function () {
        $this->lexer = new Lexer();

        $this->createParser = function (string $content) {
            $tokenStream = $this->lexer->tokenize($content);

            return new ForwardRuleParser($tokenStream, );
        };
    });

    it('parses basic forward rule with string path', function () {
        $parser = ($this->createParser)('@forward "styles";');

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(ForwardNode::class)
            ->and($result->path)->toBe('styles')
            ->and($result->namespace)->toBeNull()
            ->and($result->config)->toBe([])
            ->and($result->hide)->toBe([])
            ->and($result->show)->toBe([]);
    });

    it('parses forward rule with single quotes', function () {
        $parser = ($this->createParser)("@forward 'utilities';");

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(ForwardNode::class)
            ->and($result->path)->toBe('utilities');
    });

    it('parses forward rule with namespace', function () {
        $parser = ($this->createParser)('@forward "components" as cmp;');

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(ForwardNode::class)
            ->and($result->namespace)->toBe('cmp');
    });

    it('parses forward rule with config', function () {
        $parser = ($this->createParser)('@forward "theme" with ($primary: blue, $spacing: 10px);');

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(ForwardNode::class)
            ->and($result->config)->toBe(['primary' => 'blue', 'spacing' => '10px']);
    });

    it('parses forward rule with hide variables', function () {
        $parser = ($this->createParser)('@forward "helpers" hide $deprecated, $old-var;');

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(ForwardNode::class)
            ->and($result->hide)->toBe(['$deprecated', '$old-var']);
    });

    it('parses forward rule with show variables', function () {
        $parser = ($this->createParser)('@forward "mixins" show $button-mixin, $grid-mixin;');

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(ForwardNode::class)
            ->and($result->show)->toBe(['$button-mixin', '$grid-mixin']);
    });

    it('parses forward rule with multiple options', function () {
        $parser = ($this->createParser)('@forward "library" as lib with ($version: 2.0) hide $internal show $public;');

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(ForwardNode::class)
            ->and($result->namespace)->toBe('lib')
            ->and($result->config)->toBe(['version' => '2.0'])
            ->and($result->hide)->toBe(['$internal'])
            ->and($result->show)->toBe(['$public']);
    });

    it('throws exception when not @forward rule', function () {
        $parser = ($this->createParser)('@use "module";');

        expect(fn() => $parser->parse())->toThrow(
            SyntaxException::class,
            'Expected @forward rule, got @use'
        );
    });

    it('throws exception when string path is missing', function () {
        $parser = ($this->createParser)('@forward;');

        expect(fn() => $parser->parse())->toThrow(
            SyntaxException::class,
            'Expected string path for @forward'
        );
    });

    it('throws exception when unexpected keyword is used', function () {
        $parser = ($this->createParser)('@forward "module" invalid;');

        expect(fn() => $parser->parse())->toThrow(
            SyntaxException::class,
            'Unexpected keyword invalid in @forward rule'
        );
    });

    it('handles complex config with various value types', function () {
        $parser = ($this->createParser)('@forward "config" with ($color: #ff0000, $size: 16px, $enabled: true);');

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(ForwardNode::class)
            ->and($result->config)->toBe(['color' => '#ff0000', 'size' => '16px', 'enabled' => 'true']);
    });

    it('handles hide/show with parentheses', function () {
        $parser = ($this->createParser)('@forward "utils" hide ($private1, $private2) show ($public1, $public2);');

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(ForwardNode::class)
            ->and($result->hide)->toBe(['$private1', '$private2'])
            ->and($result->show)->toBe(['$public1', '$public2']);
    });

    it('parses forward rule with path containing dots and hyphens', function () {
        $parser = ($this->createParser)('@forward "./shared/components/button";');

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(ForwardNode::class)
            ->and($result->path)->toBe('./shared/components/button');
    });

    it('validates config parsing with reflection', function () {
        $parser = ($this->createParser)('@forward "theme" with ($primary: blue);');

        $accessor = new ReflectionAccessor($parser);
        $result   = $accessor->callMethod('parse');

        expect($result)->toBeInstanceOf(ForwardNode::class)
            ->and($result->config)->toBe(['primary' => 'blue']);
    });

    it('validates variable list parsing with reflection', function () {
        $parser = ($this->createParser)('@forward "helpers" hide $var1, $var2;');

        $accessor = new ReflectionAccessor($parser);
        $result   = $accessor->callMethod('parse');

        expect($result)->toBeInstanceOf(ForwardNode::class)
            ->and($result->hide)->toBe(['$var1', '$var2']);
    });

    it('handles empty config parentheses', function () {
        $parser = ($this->createParser)('@forward "empty" with ();');

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(ForwardNode::class)
            ->and($result->config)->toBe([]);
    });

    it('handles empty hide/show lists', function () {
        $parser = ($this->createParser)('@forward "clean" hide () show ();');

        $result = $parser->parse();

        expect($result)->toBeInstanceOf(ForwardNode::class)
            ->and($result->hide)->toBe([])
            ->and($result->show)->toBe([]);
    });
});
