<?php

declare(strict_types=1);

use DartSass\Parsers\ImportParser;
use DartSass\Parsers\Nodes\AtRuleNode;
use DartSass\Parsers\Tokens\Lexer;
use DartSass\Parsers\Tokens\Token;
use Tests\ReflectionAccessor;

describe('ImportParser', function () {
    beforeEach(function () {
        $this->lexer = new Lexer();

        $this->createParser = function (string $content): ImportParser {
            $stream = $this->lexer->tokenize($content);

            return new ImportParser($stream);
        };
    });

    describe('parse()', function () {
        it('parses simple import', function () {
            $parser = ($this->createParser)('@import "colors.scss";');

            $result = $parser->parse();

            expect($result)->toBeInstanceOf(AtRuleNode::class)
                ->and($result->name)->toBe('@import');
        });

        it('parses import with single quotes', function () {
            $parser = ($this->createParser)("@import 'variables.scss';");

            $result = $parser->parse();

            expect($result->value)->toBe('variables.scss');
        });

        it('parses import with double quotes', function () {
            $parser = ($this->createParser)('@import "mixins.scss";');

            $result = $parser->parse();

            expect($result->value)->toBe('mixins.scss');
        });

        it('parses import without quotes', function () {
            $parser = ($this->createParser)('@import reset;');

            $result = $parser->parse();

            expect($result->value)->toBe('reset');
        });

        it('parses import with url function', function () {
            $parser = ($this->createParser)('@import url("fonts.css");');

            $result = $parser->parse();

            expect($result->value)->toContain('url(');
        });

        it('parses import with url and single quotes', function () {
            $parser = ($this->createParser)("@import url('styles.css');");

            $result = $parser->parse();

            expect($result->value)->toBe('url("styles.css")');
        });

        it('parses import with url and no quotes', function () {
            $parser = ($this->createParser)('@import url(fonts.css);');

            $result = $parser->parse();

            expect($result->value)->toBe('url("fonts.css")');
        });

        it('parses import with media type', function () {
            $parser = ($this->createParser)('@import "print.scss" print;');

            $result = $parser->parse();

            expect($result->value)->toBe('"print.scss" print');
        });

        it('parses import with multiple media types', function () {
            $parser = ($this->createParser)('@import "screen.css" screen, print;');

            $result = $parser->parse();

            expect($result->value)->toBe('"screen.css" screen , print');
        });

        it('parses import with full url', function () {
            $parser = ($this->createParser)('@import "https://example.com/style.scss";');

            $result = $parser->parse();

            expect($result->value)->toBe('https://example.com/style.scss');
        });

        it('preserves spaces in import path', function () {
            $parser = ($this->createParser)('@import "folder/subfolder/file";');

            $result = $parser->parse();

            expect($result->value)->toBe('folder/subfolder/file');
        });

        it('parses import with file protocol', function () {
            $parser = ($this->createParser)('@import "file://path/to/file.scss";');

            $result = $parser->parse();

            expect($result->value)->toBe('file://path/to/file.scss');
        });

        it('handles import with interpolation', function () {
            $parser = ($this->createParser)('@import "#{$theme}/colors";');

            $result = $parser->parse();

            expect($result->value)->toContain('#{$theme}');
        });

        it('returns correct line number', function () {
            $parser = ($this->createParser)('@import "test.scss";');

            $result = $parser->parse();

            expect($result->line)->toBe(1);
        });
    });

    describe('shouldAddSpace()', function () {
        beforeEach(function () {
            $parser = ($this->createParser)('@import test;');

            $this->accessor = new ReflectionAccessor($parser);
        });

        it('does not add space after colon', function () {
            $previous = new Token('identifier', 'test', 1, 1);
            $current = new Token('colon', ':', 1, 5);

            $result = $this->accessor->callMethod('shouldAddSpace', [$previous, $current]);

            expect($result)->toBeFalse();
        });

        it('does not add space before paren close', function () {
            $previous = new Token('identifier', 'test', 1, 1);
            $current = new Token('paren_close', ')', 1, 9);

            $result = $this->accessor->callMethod('shouldAddSpace', [$previous, $current]);

            expect($result)->toBeFalse();
        });

        it('does not add space after paren open', function () {
            $previous = new Token('paren_open', '(', 1, 6);
            $current = new Token('identifier', 'test', 1, 7);

            $result = $this->accessor->callMethod('shouldAddSpace', [$previous, $current]);

            expect($result)->toBeFalse();
        });

        it('adds space for identifier after identifier', function () {
            $previous = new Token('identifier', 'test1', 1, 1);
            $current = new Token('identifier', 'test2', 1, 7);

            $result = $this->accessor->callMethod('shouldAddSpace', [$previous, $current]);

            expect($result)->toBeTrue();
        });
    });

    describe('normalizeImportPath()', function () {
        beforeEach(function () {
            $parser = ($this->createParser)('@import test;');

            $this->accessor = new ReflectionAccessor($parser);
        });

        it('normalizes import with url function', function () {
            $result = $this->accessor->callMethod('normalizeImportPath', ['url(test.css)']);

            expect($result)->toBe('url("test.css")');
        });

        it('keeps quotes in url with quoted content', function () {
            $result = $this->accessor->callMethod('normalizeImportPath', ['url("test.css")']);

            expect($result)->toBe('url("test.css")');
        });

        it('removes quotes from simple path without spaces', function () {
            $result = $this->accessor->callMethod('normalizeImportPath', ['"test.css"']);

            expect($result)->toBe('test.css');
        });

        it('preserves quotes in path with spaces', function () {
            $result = $this->accessor->callMethod('normalizeImportPath', ['"folder/sub folder/file.scss"']);

            expect($result)->toBe('"folder/sub folder/file.scss"');
        });

        it('normalizes url with single quotes', function () {
            $result = $this->accessor->callMethod('normalizeImportPath', ["url('test.css')"]);

            expect($result)->toBe('url("test.css")');
        });
    });

    describe('Error Handling', function () {
        it('throws exception for missing semicolon', function () {
            $parser = ($this->createParser)('@import "test.scss"');

            expect(fn() => $parser->parse())->toThrow(Exception::class);
        });

        it('throws exception for invalid import syntax', function () {
            $parser = ($this->createParser)('@import');

            expect(fn() => $parser->parse())->toThrow(Exception::class);
        });
    });
});
