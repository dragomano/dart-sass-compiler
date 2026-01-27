<?php

declare(strict_types=1);

namespace DartSass\Parsers;

use Closure;
use DartSass\Exceptions\SyntaxException;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\FunctionNode;
use DartSass\Parsers\Nodes\MixinNode;
use DartSass\Parsers\Nodes\NullNode;
use DartSass\Parsers\Tokens\TokenStreamInterface;

use function array_filter;
use function array_merge;
use function is_array;

class FunctionParser extends AbstractParser
{
    public function __construct(
        TokenStreamInterface     $stream,
        private readonly Closure $parseBlock,
        private readonly Closure $parseBinaryExpression
    ) {
        parent::__construct($stream);
    }

    /**
     * @throws SyntaxException
     */
    public function parse(): AstNode
    {
        return $this->parseFunction();
    }

    /**
     * @throws SyntaxException
     */
    public function parseFunction(): AstNode
    {
        [$name, $args] = $this->parseNameAndInit();

        $this->consume('paren_open');

        while (! $this->peek('paren_close')) {
            $this->skipWhitespace();

            if ($this->peek('paren_close')) {
                break;
            }

            $opToken = $this->currentToken();
            if ($opToken && $opToken->type === 'operator' && $opToken->value === ',') {
                $this->advanceToken();

                continue;
            }

            if ($this->peek('variable')) {
                $argName = $this->consume('variable')->value;

                $arbitrary = false;

                if ($this->peek('spread_operator')) {
                    $this->consume('spread_operator');

                    $arbitrary = true;

                    $this->skipWhitespace();

                    if (! $this->peek('paren_close')) {
                        throw new SyntaxException(
                            'Arbitrary argument (...) must be the last parameter',
                            $this->currentToken()->line,
                            $this->currentToken()->column
                        );
                    }
                }

                if ($this->peek('colon')) {
                    $this->consume('colon');

                    $defaultValue = $this->parseBinaryExpression();

                    $args[] = ['name' => $argName, 'arbitrary' => $arbitrary, 'default' => $defaultValue];
                } else {
                    $args[] = ['name' => $argName, 'arbitrary' => $arbitrary];
                }
            } else {
                $argName = $this->consume('identifier')->value;
                $args[]  = ['name' => $argName, 'arbitrary' => false];
            }

            if (! $this->peek('paren_close')) {
                $this->skipWhitespace();

                $commaToken = $this->currentToken();
                if ($commaToken && $commaToken->type === 'operator' && $commaToken->value === ',') {
                    $this->advanceToken();
                }
            }
        }

        $this->consume('paren_close');
        $this->skipWhitespace();
        $this->consume('brace_open');

        $content = $this->parseBody();

        return new FunctionNode($name, $args, $content);
    }

    public function parseMixin(): AstNode
    {
        [$name, $args] = $this->parseNameAndInit();

        while (! $this->peek('brace_open')) {
            $this->skipWhitespace();

            if ($this->peek('brace_open')) {
                break;
            }

            if ($this->peek('paren_open')) {
                $this->consume('paren_open');

                while (! $this->peek('paren_close')) {
                    $this->skipWhitespace();

                    if ($this->peek('paren_close')) {
                        break;
                    }

                    if ($this->peek('variable')) {
                        $varToken = $this->consume('variable');

                        $argName = $varToken->value;

                        if ($this->peek('spread_operator')) {
                            $this->consume('spread_operator');

                            $argName .= '...';
                        }

                        if ($this->peek('colon')) {
                            $this->consume('colon');

                            $defaultValue = $this->parseBinaryExpression();

                            $args[$argName] = $defaultValue;
                        } else {
                            $args[$argName] = new NullNode($varToken->line);
                        }
                    } else {
                        $this->consume('identifier');
                    }

                    if (! $this->peek('paren_close')) {
                        $this->skipWhitespace();

                        $commaToken = $this->currentToken();
                        if ($commaToken && $commaToken->type === 'operator' && $commaToken->value === ',') {
                            $this->advanceToken();
                        }
                    }
                }

                $this->consume('paren_close');

                break;
            }

            $this->advanceToken();
        }

        $this->skipWhitespace();
        $this->consume('brace_open');

        $content = $this->parseBody();
        $content = array_filter($content, function ($node) {
            if (is_array($node)) {
                return ! empty($node);
            }

            return $node && isset($node->type) && $node->type !== '';
        });

        return new MixinNode($name, $args, $content);
    }

    private function parseNameAndInit(): array
    {
        $this->consume('at_rule');
        $this->skipWhitespace();

        $token = $this->expectAny('identifier', 'function');

        $name = $token->value;
        $args = [];

        return [$name, $args];
    }

    private function parseBinaryExpression(): AstNode
    {
        return ($this->parseBinaryExpression)();
    }

    private function parseBody(): array
    {
        $body = ($this->parseBlock)();

        return $body['items'] ?? array_merge($body['declarations'], $body['nested']);
    }
}
