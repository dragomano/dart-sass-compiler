<?php

declare(strict_types=1);

namespace DartSass\Parsers;

use DartSass\Exceptions\SyntaxException;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\IdentifierNode;
use DartSass\Parsers\Nodes\InterpolationNode;
use DartSass\Parsers\Nodes\NumberNode;
use DartSass\Parsers\Nodes\SelectorNode;
use DartSass\Parsers\Nodes\StringNode;
use DartSass\Parsers\Nodes\VariableNode;
use DartSass\Parsers\Tokens\Token;
use DartSass\Utils\StringFormatter;

use function in_array;
use function preg_match;
use function sprintf;
use function str_ends_with;
use function str_starts_with;
use function substr;
use function trim;

class SelectorParser extends AbstractParser
{
    private const SELECTOR_TOKENS = [
        'selector'   => true,
        'identifier' => true,
        'colon'      => true,
        'comma'      => true,
        'whitespace' => true,
        'variable'   => true,
        'number'     => true,
        'asterisk'   => true,
        'string'     => true,
        'hex_color'  => true,
    ];

    private const ATTRIBUTE_WITH_QUOTES = '/\[([^]=]+)([~*^|$!]?=)(["\']?)([^"\'\s]+)(\3)]/';

    private const SAFE_UNQUOTED = '/^[a-zA-Z0-9_-]+$/';

    /**
     * @throws SyntaxException
     */
    public function parse(): AstNode
    {
        $value = '';
        $line  = $this->currentToken()->line;

        while (
            ($token = $this->currentToken())
            && ! $this->matchesAny('brace_open', 'semicolon')
        ) {
            if ($token->type === 'comment') {
                $this->advanceToken();

                continue;
            }

            if (isset(self::SELECTOR_TOKENS[$token->type])) {
                if ($value !== '' && $this->needsSpaceBeforeToken($value, $token)) {
                    $value .= ' ';
                }

                $value .= $token->value;

                $this->advanceToken();
            } elseif ($token->type === 'function') {
                $value .= $this->parsePseudoClassFunction($token);
            } elseif ($token->type === 'operator') {
                $value .= $token->value;

                $this->advanceToken();
            } elseif ($token->type === 'double_hash_interpolation') {
                $this->advanceToken();

                $variableToken = $this->consume('variable');

                $this->consume('brace_close');

                $value = StringFormatter::concat($value, StringFormatter::concat('#', $variableToken->value));
            } elseif ($token->type === 'attribute_selector') {
                $value .= $this->optimizeAttributeSelector($token->value);

                $this->advanceToken();
            } elseif ($token->type === 'interpolation_open') {
                $value .= $this->parseInterpolationInSelector();
            } elseif ($token->type === 'at_rule') {
                if ($token->value === '@content') {
                    $value .= '@content';

                    $this->advanceToken();
                } else {
                    throw new SyntaxException(
                        sprintf('Unexpected at_rule in selector: %s', $token->value),
                        $token->line,
                        $token->column
                    );
                }
            } else {
                throw new SyntaxException(
                    sprintf('Unexpected token in selector: %s', $token->type),
                    $token->line,
                    $token->column
                );
            }
        }

        return new SelectorNode(trim($value), $line);
    }

    protected function parsePseudoClassFunction(Token $token): string
    {
        $result = $token->value;

        $this->advanceToken();
        $this->consume('paren_open');

        $result .= '(';

        $parenLevel = 1;
        while ($this->currentToken() && $parenLevel > 0) {
            $current = $this->currentToken();

            if ($current->type === 'paren_open') {
                $parenLevel++;
                $result .= $current->value;
            } elseif ($current->type === 'paren_close') {
                $parenLevel--;
                if ($parenLevel > 0) {
                    $result .= $current->value;
                }
            } else {
                $result .= $current->value;
            }

            $this->advanceToken();
        }

        $result .= ')';

        return $result;
    }

    private function needsSpaceBeforeToken(string $currentValue, Token $nextToken): bool
    {
        $lastChar = substr($currentValue, -1);

        if (in_array($lastChar, ['.', '#', '>', '+', '~', '&', ':'], true)) {
            return false;
        }

        if (in_array($nextToken->value, ['.', '#', '>', '+', '~', '&', ':'], true)) {
            return false;
        }

        if ($nextToken->type === 'identifier') {
            return true;
        }

        return false;
    }

    private function optimizeAttributeSelector(string $selector): string
    {
        if (preg_match(self::ATTRIBUTE_WITH_QUOTES, $selector, $matches)) {
            $attribute = $matches[1];
            $operator  = $matches[2];
            $value     = $matches[4];

            if (preg_match(self::SAFE_UNQUOTED, $value)) {
                return "[$attribute$operator$value]";
            }
        }

        return $selector;
    }

    /**
     * @throws SyntaxException
     */
    private function parseInterpolationInSelector(): string
    {
        $this->consume('interpolation_open');

        $expression = $this->parseExpression();

        $this->consume('brace_close');

        return $this->formatExpressionForSelector($expression);
    }

    /**
     * @throws SyntaxException
     */
    private function parseExpression(): AstNode
    {
        $parser = new ExpressionParser($this->getStream());

        return $parser->parse();
    }

    private function formatExpressionForSelector(AstNode $expr): string
    {
        if ($expr instanceof VariableNode) {
            return $expr->name;
        }

        if ($expr instanceof IdentifierNode || $expr instanceof NumberNode) {
            return $expr->value;
        }

        if ($expr instanceof StringNode) {
            $value = $expr->value;

            if (str_starts_with($value, '"') && str_ends_with($value, '"')) {
                $value = trim($value, '"');
            }

            return $value;
        }

        if ($expr instanceof InterpolationNode) {
            return StringFormatter::concatMultiple(['#{', $this->formatExpressionForSelector($expr->expression), '}']);
        }

        return 'expression';
    }
}
