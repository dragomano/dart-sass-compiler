<?php

declare(strict_types=1);

use DartSass\Parsers\BlockParser;
use DartSass\Parsers\ExpressionParser;
use DartSass\Parsers\Nodes\NodeType;
use DartSass\Parsers\Rules\MediaRuleParser;
use DartSass\Parsers\SelectorParser;
use DartSass\Parsers\Tokens\Lexer;

describe('MediaRuleParser', function () {
    beforeEach(function () {
        $this->lexer = new Lexer();

        $this->createParser = function (string $content): MediaRuleParser {
            $stream = $this->lexer->tokenize($content);

            $expressionParser = new ExpressionParser($stream);
            $selectorParser   = new SelectorParser($stream, fn() => $expressionParser->parse());

            $parseExpression         = fn() => $expressionParser->parse();
            $parseArgumentExpression = fn() => $expressionParser->parseArgumentList();
            $parseSelector           = fn() => $selectorParser->parse();

            $blockParser = new BlockParser(
                $stream,
                $parseExpression,
                $parseArgumentExpression,
                $parseSelector
            );

            $parseAtRuleClosure = Closure::bind(fn() => $blockParser->parseAtRule(), $blockParser, BlockParser::class);

            return new MediaRuleParser(
                $stream,
                $parseAtRuleClosure,
                fn() => $blockParser->parseInclude(),
                fn() => $blockParser->parseVariable(),
                fn() => $blockParser->parseRule(),
                fn() => $blockParser->parseDeclaration()
            );
        };
    });

    describe('parse()', function () {
        it('parses basic @media rule', function () {
            $parser = ($this->createParser)('@media screen { color: red; }');

            $result = $parser->parse();

            expect($result->query)->toBe('screen')
                ->and($result->type)->toBe(NodeType::MEDIA)
                ->and($result->body['declarations'])->toHaveCount(1);
        });

        it('parses @media with complex query', function () {
            $parser = ($this->createParser)('@media screen and (min-width: 768px) { color: blue; }');

            $result = $parser->parse();

            expect($result->query)->toBe('screen and (min-width: 768px)');
        });

        it('parses @media with multiple conditions', function () {
            $parser = ($this->createParser)('@media screen, print { color: green; }');

            $result = $parser->parse();

            expect($result->query)->toContain('screen')
                ->and($result->query)->toContain('print');
        });

        it('parses @media with and/or operators', function () {
            $parser = ($this->createParser)('@media (min-width: 600px) and (max-width: 1200px) { padding: 10px; }');

            $result = $parser->parse();

            expect($result->query)->toContain('and');
        });

        it('parses @media with only keyword', function () {
            $parser = ($this->createParser)('@media only screen { font-size: 16px; }');

            $result = $parser->parse();

            expect($result->query)->toContain('only')
                ->and($result->query)->toContain('screen');
        });

        it('parses @media with multiple declarations', function () {
            $parser = ($this->createParser)('@media screen {
                color: red;
                font-size: 14px;
                margin: 10px;
            }');

            $result = $parser->parse();

            expect($result->body['declarations'])->toHaveCount(3);
        });

        it('parses @media with nested rules', function () {
            $parser = ($this->createParser)('@media screen {
                .button {
                    padding: 8px;
                }
            }');

            $result = $parser->parse();

            expect($result->body['nested'])->toHaveCount(1)
                ->and($result->body['nested'][0]->selector->value)->toBe('.button');
        });

        it('parses @media with includes', function () {
            $parser = ($this->createParser)('@media screen {
                @include mixin-name;
            }');

            $result = $parser->parse();

            expect($result->body['nested'])->toHaveCount(1)
                ->and($result->body['nested'][0]->name)->toBe('mixin-name');
        });

        it('parses @media with includes and arguments', function () {
            $parser = ($this->createParser)('@media screen {
                @include button-style(blue, large);
            }');

            $result = $parser->parse();

            expect($result->body['nested'][0]->name)->toBe('button-style')
                ->and($result->body['nested'][0]->args)->toHaveCount(2);
        });

        it('parses @media with variables', function () {
            $parser = ($this->createParser)('@media screen {
                $primary-color: #ff0000;
            }');

            $result = $parser->parse();

            expect($result->body['nested'])->toHaveCount(1)
                ->and($result->body['nested'][0]->name)->toBe('$primary-color');
        });

        it('parses @media with mixed content', function () {
            $parser = ($this->createParser)('@media screen {
                $bg-color: #fff;
                color: $text-color;

                .header {
                    font-size: 18px;
                }

                @include clearfix;
            }');

            $result = $parser->parse();

            expect($result->body['declarations'])->toHaveCount(1)
                ->and($result->body['nested'])->toHaveCount(3);
        });

        it('parses @media with feature query', function () {
            $parser = ($this->createParser)('@media (prefers-color-scheme: dark) {
                body {
                    background-color: #000;
                    color: #fff;
                }
            }');

            $result = $parser->parse();

            expect($result->query)->toContain('prefers-color-scheme')
                ->and($result->body['nested'])->toHaveCount(1);
        });

        it('parses @media with range conditions', function () {
            $parser = ($this->createParser)('@media (width >= 320px) and (width <= 1024px) {
                .container {
                    width: 100%;
                }
            }');

            $result = $parser->parse();

            expect($result->query)->toContain('and');
        });

        it('parses @media with nested rules containing class selector', function () {
            $parser = ($this->createParser)('@media screen {
                .button:hover {
                    color: orange;
                }
            }');

            $result = $parser->parse();

            expect($result->body['nested'])->toHaveCount(1)
                ->and($result->body['nested'][0]->selector->value)->toBe('.button:hover');
        });

        it('parses @media with nested rules containing id selector', function () {
            $parser = ($this->createParser)('@media screen {
                #header {
                    background: blue;
                }
            }');

            $result = $parser->parse();

            expect($result->body['nested'][0]->selector->value)->toBe('#header');
        });

        it('preserves line number', function () {
            $parser = ($this->createParser)('@media screen {
                color: red;
            }');

            $result = $parser->parse();

            expect($result->line)->toBeGreaterThan(0);
        });

        it('handles empty @media body', function () {
            $parser = ($this->createParser)('@media screen {}');

            $result = $parser->parse();

            expect($result->query)->toBe('screen')
                ->and($result->body['declarations'])->toHaveCount(0)
                ->and($result->body['nested'])->toHaveCount(0);
        });

        it('parses @media with declarations using variables', function () {
            $parser = ($this->createParser)('@media screen {
                $color: #ff5500;
                background: $color;
            }');

            $result = $parser->parse();

            expect($result->body['nested'])->toHaveCount(1)
                ->and($result->body['declarations'])->toHaveCount(1);
        });

        it('parses @media with complex selector', function () {
            $parser = ($this->createParser)('@media screen {
                .parent > .child:first-child {
                    margin: 0;
                }
            }');

            $result = $parser->parse();

            expect($result->body['nested'][0]->selector->value)->toContain('.parent');
        });

        it('parses @media with attribute selector', function () {
            $parser = ($this->createParser)('@media screen {
                [data-type="primary"] {
                    border: 1px solid;
                }
            }');

            $result = $parser->parse();

            expect($result->body['nested'][0]->selector->value)->toContain('[data-type');
        });

        it('parses @media with color declarations', function () {
            $parser = ($this->createParser)('@media screen {
                background-color: #ff0000;
                color: rgb(0, 255, 0);
            }');

            $result = $parser->parse();

            expect($result->body['declarations'])->toHaveCount(2);
        });

        it('parses @media with calc expressions', function () {
            $parser = ($this->createParser)('@media screen {
                width: calc(100% - 20px);
            }');

            $result = $parser->parse();

            expect($result->body['declarations'])->toHaveCount(1);
        });

        it('parses @media with @keyframes at top level', function () {
            $parser = ($this->createParser)('@media screen {
                @keyframes fade {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
            }');

            $result = $parser->parse();

            expect($result->body['nested'])->toHaveCount(1)
                ->and($result->body['nested'][0]->name)->toBe('fade');
        });

        it('parses @media with all media type', function () {
            $parser = ($this->createParser)('@media all { color: black; }');

            $result = $parser->parse();

            expect($result->query)->toBe('all');
        });

        it('parses @media with tv media type', function () {
            $parser = ($this->createParser)('@media tv { color: red; }');

            $result = $parser->parse();

            expect($result->query)->toBe('tv');
        });

        it('parses @media with speech media type', function () {
            $parser = ($this->createParser)('@media speech { volume: 50%; }');

            $result = $parser->parse();

            expect($result->query)->toBe('speech');
        });

        it('parses @media with orientation', function () {
            $parser = ($this->createParser)('@media (orientation: portrait) { .portrait { display: block; } }');

            $result = $parser->parse();

            expect($result->query)->toContain('orientation');
        });

        it('parses @media with resolution', function () {
            $parser = ($this->createParser)('@media (min-resolution: 300dpi) { img { image-rendering: pixelated; } }');

            $result = $parser->parse();

            expect($result->query)->toContain('resolution');
        });

        it('parses @media with hover media feature', function () {
            $parser = ($this->createParser)('@media (hover: hover) { a:hover { color: blue; } }');

            $result = $parser->parse();

            expect($result->query)->toContain('hover');
        });

        it('parses @media with pointer media feature', function () {
            $parser = ($this->createParser)('@media (pointer: fine) { .button { cursor: pointer; } }');

            $result = $parser->parse();

            expect($result->query)->toContain('pointer');
        });

        it('parses @media with not operator', function () {
            $parser = ($this->createParser)('@media not screen { color: red; }');

            $result = $parser->parse();

            expect($result->query)->toContain('not');
        });

        it('parses @media with nested @media', function () {
            $parser = ($this->createParser)('@media screen {
                @media (min-width: 768px) {
                    .tablet {
                        padding: 20px;
                    }
                }
            }');

            $result = $parser->parse();

            expect($result->body['nested'])->toHaveCount(1)
                ->and($result->body['nested'][0]->query)->toContain('min-width');
        });

        it('parses @media with @use directive', function () {
            $parser = ($this->createParser)('@media screen {
                @use "library";
            }');

            $result = $parser->parse();

            expect($result->body['nested'])->toHaveCount(1);
        });

        it('parses @media with @forward directive', function () {
            $parser = ($this->createParser)('@media screen {
                @forward "variables";
            }');

            $result = $parser->parse();

            expect($result->body['nested'])->toHaveCount(1);
        });

        it('parses @media with declarations containing important keyword', function () {
            $parser = ($this->createParser)('@media screen {
                color: red !important;
            }');

            $result = $parser->parse();

            expect($result->body['declarations'])->toHaveCount(1);
        });

        it('parses @media with type selector', function () {
            $parser = ($this->createParser)('@media screen {
                div {
                    padding: 10px;
                }
            }');

            $result = $parser->parse();

            expect($result->body['nested'][0]->selector->value)->toBe('div');
        });

        it('parses @media with descendant combinator', function () {
            $parser = ($this->createParser)('@media screen {
                .container p {
                    margin: 5px;
                }
            }');

            $result = $parser->parse();

            expect($result->body['nested'][0]->selector->value)->toContain('.container');
        });

        it('parses @media with negated pseudo-class', function () {
            $parser = ($this->createParser)('@media screen {
                div:not(.hidden) {
                    display: block;
                }
            }');

            $result = $parser->parse();

            expect($result->body['nested'][0]->selector->value)->toContain(':not');
        });

        it('parses @media with grid/flex declarations', function () {
            $parser = ($this->createParser)('@media screen {
                .grid {
                    display: grid;
                    grid-template-columns: repeat(3, 1fr);
                    gap: 10px;
                }
            }');

            $result = $parser->parse();

            expect($result->body['nested'][0]->selector->value)->toBe('.grid')
                ->and($result->body['nested'][0]->declarations)->toHaveCount(3);
        });

        it('parses @media with multiple nested rules', function () {
            $parser = ($this->createParser)('@media screen {
                .header {
                    font-size: 18px;
                }
                .footer {
                    font-size: 14px;
                }
                .sidebar {
                    width: 200px;
                }
            }');

            $result = $parser->parse();

            expect($result->body['nested'])->toHaveCount(3);
        });

        it('parses @media with nested rules and declarations mixed', function () {
            $parser = ($this->createParser)('@media screen {
                color: red;
                .header {
                    font-size: 16px;
                    font-weight: bold;
                }
                background: blue;
            }');

            $result = $parser->parse();

            expect($result->body['declarations'])->toHaveCount(2)
                ->and($result->body['nested'])->toHaveCount(1);
        });

        it('parses @media with whitespace between elements', function () {
            $parser = ($this->createParser)('@media screen {   color: red;   }');

            $result = $parser->parse();

            expect($result->body['declarations'])->toHaveCount(1);
        });

        it('parses @media with multiple spaces between declarations', function () {
            $parser = ($this->createParser)('@media screen {
                color: red;


                font-size: 14px;
            }');

            $result = $parser->parse();

            expect($result->body['declarations'])->toHaveCount(2);
        });

        it('parses @media with tabs and spaces mixed', function () {
            $parser = ($this->createParser)('@media screen {
	color: red;
	padding: 10px;
    }');

            $result = $parser->parse();

            expect($result->body['declarations'])->toHaveCount(2);
        });

        it('parses @media with whitespace before closing brace', function () {
            $parser = ($this->createParser)('@media screen {
                color: red;

            }');

            $result = $parser->parse();

            expect($result->body['declarations'])->toHaveCount(1)
                ->and($result->body['nested'])->toHaveCount(0);
        });

        it('parses @media with whitespace between nested rules', function () {
            $parser = ($this->createParser)('@media screen {

                .header {
                    font-size: 18px;
                }


                .footer {
                    font-size: 14px;
                }

            }');

            $result = $parser->parse();

            expect($result->body['nested'])->toHaveCount(2);
        });

        it('parses @media with whitespace between includes and rules', function () {
            $parser = ($this->createParser)('@media screen {
                @include mixin-one;

                .component {
                    width: 100%;
                }

                @include mixin-two;
            }');

            $result = $parser->parse();

            expect($result->body['nested'])->toHaveCount(3);
        });

        it('parses @media with @include containing content block', function () {
            $parser = ($this->createParser)('@media screen {
                @include theme {
                    background: blue;
                }
            }');

            $result = $parser->parse();

            expect($result->body['nested'])->toHaveCount(1)
                ->and($result->body['nested'][0]->name)->toBe('theme')
                ->and($result->body['nested'][0]->body)->not->toBeNull();
        });

        it('parses @media with selector after whitespace loop', function () {
            $parser = ($this->createParser)('@media screen {   .class { color: red; }   }');

            $result = $parser->parse();

            expect($result->body['nested'])->toHaveCount(1)
                ->and($result->body['nested'][0]->selector->value)->toBe('.class');
        });

        it('parses @media with break on whitespace before closing brace', function () {
            $parser = ($this->createParser)('@media screen { color: red;   }');

            $result = $parser->parse();

            expect($result->body['declarations'])->toHaveCount(1)
                ->and($result->body['nested'])->toHaveCount(0);
        });

        it('parses @media with whitespace between selector and rule', function () {
            $parser = ($this->createParser)('@media screen {    .test { margin: 0; }   }');

            $result = $parser->parse();

            expect($result->body['nested'])->toHaveCount(1)
                ->and($result->body['nested'][0]->selector->value)->toBe('.test');
        });

        it('parses @media with operator selector after whitespace', function () {
            $parser = ($this->createParser)('@media screen {   &.active { display: block; }   }');

            $result = $parser->parse();

            expect($result->body['nested'])->toHaveCount(1);
        });

        it('parses @media with multiple whitespace tokens then selector', function () {
            $parser = ($this->createParser)('@media screen {


                .class { color: red; }
            }');

            $result = $parser->parse();

            expect($result->body['nested'])->toHaveCount(1)
                ->and($result->body['nested'][0]->selector->value)->toBe('.class');
        });

        it('parses @media with whitespace then closing brace after declaration', function () {
            $parser = ($this->createParser)('@media screen { color: red;   }');

            $result = $parser->parse();

            expect($result->body['declarations'])->toHaveCount(1)
                ->and($result->body['nested'])->toHaveCount(0);
        });

        it('parses @media with whitespace loop then selector', function () {
            $parser = ($this->createParser)('@media screen {


                .test { margin: 0; }
            }');

            $result = $parser->parse();

            expect($result->body['nested'])->toHaveCount(1)
                ->and($result->body['nested'][0]->selector->value)->toBe('.test');
        });

        it('parses @media with tabs and newlines then selector', function () {
            $parser = ($this->createParser)('@media screen {




                .class { color: blue; }
            }');

            $result = $parser->parse();

            expect($result->body['nested'])->toHaveCount(1);
        });

        it('parses @media with spaces and newlines before closing brace', function () {
            $parser = ($this->createParser)('@media screen { color: red;


            }');

            $result = $parser->parse();

            expect($result->body['declarations'])->toHaveCount(1);
        });

        it('parses @media with declaration through handleOtherTokensInBlock', function () {
            $parser = ($this->createParser)('@media screen { color: red; }');

            $result = $parser->parse();

            expect($result->body['declarations'])->toHaveCount(1)
                ->and($result->body['declarations'][0])->toHaveKey('color');
        });

        it('parses @media with multiple declarations through handleOtherTokensInBlock', function () {
            $parser = ($this->createParser)('@media screen { color: red; font-size: 14px; margin: 10px; }');

            $result = $parser->parse();

            expect($result->body['declarations'])->toHaveCount(3);
        });

        it('parses @media with attribute selector through handleOtherTokensInBlock', function () {
            $parser = ($this->createParser)('@media screen { [data-attr] { color: red; } }');

            $result = $parser->parse();

            expect($result->body['nested'])->toHaveCount(1);
        });

        it('parses @media with function pseudo-class selector', function () {
            $parser = ($this->createParser)('@media screen { div:not(.class) { color: red; } }');

            $result = $parser->parse();

            expect($result->body['nested'])->toHaveCount(1)
                ->and($result->body['nested'][0]->selector->value)->toContain(':not');
        });

        it('parses @media with pseudo-class without function', function () {
            $parser = ($this->createParser)('@media screen { div:hover { color: red; } }');

            $result = $parser->parse();

            expect($result->body['nested'])->toHaveCount(1)
                ->and($result->body['nested'][0]->selector->value)->toContain(':hover');
        });

        it('parses @media with nested parentheses in pseudo-class', function () {
            $parser = ($this->createParser)('@media screen { div:not(.class) { color: red; } }');

            $result = $parser->parse();

            expect($result->body['nested'])->toHaveCount(1);
        });

        it('parses @media with shouldAddSpace returning false at line 169', function () {
            $parser = ($this->createParser)('@media screen { div { color: red; } }');

            $result = $parser->parse();

            expect($result->body['nested'])->toHaveCount(1);
        });

        it('parses @media with shouldAddSpace and keyword at line 182', function () {
            $parser = ($this->createParser)('@media screen and (min-width: 768px) { color: red; }');

            $result = $parser->parse();

            expect($result->query)->toBe('screen and (min-width: 768px)');
        });

        it('parses @media with shouldAddSpace number after paren at line 204', function () {
            $parser = ($this->createParser)('@media (min-width: 768px) and (max-width: 1024px) { color: red; }');

            $result = $parser->parse();

            expect($result->query)->toContain('and');
        });
    });
});
