<?php

declare(strict_types=1);

namespace DartSass\Parsers;

use DartSass\Exceptions\SyntaxException;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\RuleNode;
use DartSass\Parsers\Nodes\SelectorNode;
use DartSass\Parsers\Nodes\VariableDeclarationNode;
use DartSass\Parsers\Nodes\IncludeNode;

use function array_pop;
use function sprintf;
use function strlen;
use function substr;
use function trim;

class SassParser extends ScssParser
{
    private const PSEUDO_CLASS_FUNCTIONS = [
        'has'     => true,
        'not'     => true,
        'is'      => true,
        'where'   => true,
        'matches' => true,
        'any'     => true,
        'all'     => true,
        'scope'   => true,
    ];

    private int $indentLevel = 0;

    private array $indentStack = [];

    /**
     * @throws SyntaxException
     */
    public function parseRule(): AstNode
    {
        $selector      = $this->parseSelector();
        $currentIndent = $selector->properties['indent'];

        $block = $this->parseBlock();

        return new RuleNode(
            $selector,
            $block['declarations'],
            $block['nested'],
            $selector->properties['line'],
            indent: $currentIndent,
        );
    }

    /**
     * @throws SyntaxException
     */
    public function parseBlock(): array
    {
        $declarations  = $nested = [];
        $currentIndent = $this->indentLevel;

        // Save the original currentIndent to prevent it from being changed
        $originalCurrentIndent = $currentIndent;

        while (true) {
            $this->skipEmptyLines();
            $savedPosition = $this->stream->getPosition();
            $this->updateIndentLevel();

            if (! $this->currentToken()) {
                $this->stream->setPosition($savedPosition);
                break;
            }

            // Prevent premature termination for @if rules at the same indent level
            $isSameLevelIf = $this->currentToken()->type === 'at_rule'
                && $this->currentToken()->value === '@if'
                && $this->indentLevel === $originalCurrentIndent;

            if ($this->indentLevel <= $originalCurrentIndent && !$isSameLevelIf) {
                $this->stream->setPosition($savedPosition);
                break;
            }

            $token     = $this->currentToken();
            $tokenType = $token->type;
            $item      = null;

            switch ($tokenType) {
                case 'at_rule':
                    $item = match ($token->value) {
                        '@return'  => $this->parseReturn(),
                        '@include' => $this->parseInclude(),
                        default    => $this->parseAtRule(),
                    };

                    $nested[] = $item;
                    break;

                case 'variable':
                    $item = $this->parseVariable();
                    $nested[] = $item;
                    break;

                case 'selector':
                case 'asterisk':
                case 'attribute_selector':
                    $item = $this->parseRule();
                    $nested[] = $item;
                    break;

                case 'operator':
                    $tokenValue = $token->value;
                    if ($tokenValue === '>' || isset(parent::NESTED_SELECTOR_OPERATORS[$tokenValue]) || $tokenValue === '[') {
                        $item = $this->parseRule();
                        $nested[] = $item;
                    } else {
                        $item = $this->parseDeclaration();
                        $declarations[] = $item;
                    }

                    break;

                case 'identifier':
                    $savedPos = $this->stream->getPosition();
                    $this->stream->advance();
                    $this->stream->skipWhitespace();

                    if ($this->stream->current()?->type === 'colon') {
                        $this->stream->advance();

                        $nextToken = $this->stream->current();
                        if ($nextToken && in_array($nextToken->type, ['pseudo_class', 'identifier'], true)) {
                            $this->stream->setPosition($savedPos);
                            $item = $this->parseRule();
                            $nested[] = $item;
                        } else {
                            $this->stream->skipWhitespace();
                            $isSelector = $this->checkIfSelector();
                            $this->stream->setPosition($savedPos);

                            if ($isSelector) {
                                $item = $this->parseRule();
                                $nested[] = $item;
                            } else {
                                $item = $this->parseDeclaration();
                                $declarations[] = $item;
                            }
                        }
                    } else {
                        $this->stream->setPosition($savedPos);
                        $item = $this->parseRule();
                        $nested[] = $item;
                    }

                    break;

                case 'function':
                    // Check if this is a pseudo-class function (like :has, :not, etc.)
                    if (isset(self::PSEUDO_CLASS_FUNCTIONS[$token->value])) {
                        $item = $this->parseRule();
                        $nested[] = $item;
                    } else {
                        $item = $this->parseDeclaration();
                        $declarations[] = $item;
                    }

                    break;

                default:
                    $item = $this->parseDeclaration();
                    $declarations[] = $item;
                    break;
            }
        }

        return [
            'declarations' => $declarations,
            'nested'       => $nested,
            'items'        => array_merge($declarations, $nested),
        ];
    }

    public function parseInclude(): AstNode
    {
        $this->consume('at_rule');
        $this->stream->skipWhitespace();

        $token = $this->stream->expectAny('identifier', 'function');
        $name = $token->value;

        while ($this->stream->current()->type === 'operator' && $this->stream->current()->value === '.') {
            $this->stream->consume('operator');

            $nextToken = $this->stream->expectAny('identifier', 'function');
            $name .= '.' . $nextToken->value;
        }

        $args = [];

        while (! $this->stream->matchesAny('newline', 'brace_open')) {
            $this->stream->skipWhitespace();

            if ($this->stream->matchesAny('newline', 'brace_open')) {
                break;
            }

            if ($this->stream->matches('paren_open')) {
                $this->stream->consume('paren_open');

                while (! $this->stream->matches('paren_close')) {
                    $this->stream->skipWhitespace();

                    if ($this->stream->matches('paren_close')) {
                        break;
                    }

                    $argValue = $this->parseBinaryExpression(0);
                    $args[] = $argValue;

                    if (! $this->stream->matches('paren_close')) {
                        $this->stream->skipWhitespace();

                        $commaToken = $this->stream->current();
                        if ($commaToken && $commaToken->type === 'operator' && $commaToken->value === ',') {
                            $this->stream->advance();
                        }
                    }
                }

                $this->stream->consume('paren_close');
                break;
            }

            $this->stream->advance();
        }

        $content = null;
        if ($this->stream->matches('brace_open')) {
            $this->stream->consume('brace_open');
            $content = $this->parseBody();
        }

        return new IncludeNode($name, $args, $content);
    }

    public function updateIndentLevel(): void
    {
        $currentIndent = 0;

        while ($this->peek('whitespace')) {
            $currentIndent += strlen($this->currentToken()->value);
            $this->advanceToken();
        }

        $currentIndent = (int) ($currentIndent / 2);

        if ($currentIndent > $this->indentLevel) {
            $this->indentStack[] = $this->indentLevel;
            $this->indentLevel = $currentIndent;
        } elseif ($currentIndent < $this->indentLevel) {
            while ($currentIndent < $this->indentLevel && $this->indentStack) {
                $this->indentLevel = (int) array_pop($this->indentStack);
            }
        }
    }

    protected function buildVariableNode(Token $token, AstNode $value, bool $global, bool $default): AstNode
    {
        while ($this->currentToken() && $this->peekMatches('newline', 'whitespace')) {
            $this->advanceToken();
            $this->skipEmptyLines();
            $this->updateIndentLevel();
        }

        return new VariableDeclarationNode(
            $token->value,
            $value,
            $token->line,
            $global,
            $default,
            $this->indentLevel
        );
    }

    protected function checkIfSelector(): bool
    {
        $this->stream->skipWhitespace();

        if ($this->stream->matches('function')) {
            // Check if this is a pseudo-class function
            $functionName = $this->stream->current()->value;

            return isset(self::PSEUDO_CLASS_FUNCTIONS[$functionName]);
        }

        if ($this->stream->matches('identifier')) {
            $this->stream->advance();

            if ($this->stream->matches('colon')) {
                $this->stream->advance();

                if ($this->stream->matches('function')) {
                    $functionName = $this->stream->current()->value;
                    return isset(self::PSEUDO_CLASS_FUNCTIONS[$functionName]);
                }
            }
        }

        if ($this->stream->matches('operator')) {
            $operatorValue = $this->stream->current()->value;

            // Check if this is a combinator (>, +, ~) which can be a selector
            if ($operatorValue === '>' || isset(parent::NESTED_SELECTOR_OPERATORS[$operatorValue])) {
                return true;
            }
        }

        if ($this->stream->matches('attribute_selector')) {
            return true;
        }

        return false;
    }

    /**
     * @throws SyntaxException
     */
    private function parseSelector(): AstNode
    {
        $value = '';

        $line = $this->currentToken()->line;
        $indent = 0;
        $tokens = $this->getTokens();
        $currentPos = $this->getTokenIndex();

        while ($currentPos > 0) {
            $currentPos--;
            $token = $tokens[$currentPos] ?? null;

            if ($token && $token->type === 'whitespace') {
                $indent = (int) (strlen($token->value) / 2);
                break;
            } elseif ($token && $token->type === 'newline') {
                break;
            }
        }

        $afterComma = false;
        while ($this->currentToken() && ! $this->stream->matches('brace_open')) {
            $token = $this->currentToken();

            if ($token->type === 'newline') {
                if ($afterComma) {
                    $value .= ' ';
                    $afterComma = false;
                    $this->advanceToken();
                    continue;
                } else {
                    break;
                }
            }

            if ($token->type === 'operator' && in_array($token->value, ['.', '#', '%'], true)) {
                $value .= $token->value;
                $this->advanceToken();
                if ($this->currentToken() && $this->currentToken()->type === 'identifier') {
                    $value .= $this->currentToken()->value;
                    $this->advanceToken();
                }

                continue;
            }

            if ($token->type === 'function') {
                $value .= $this->parsePseudoClassFunction($token);
                continue;
            }

            match ($token->type) {
                'selector',
                'identifier',
                'colon',
                'whitespace',
                'number',
                'string',
                'variable',
                'paren_open',
                'paren_close',
                'operator',
                'semicolon',
                'interpolation_open',
                'brace_close',
                'double_hash_interpolation',
                'brace_open',
                'attribute_selector',
                'asterisk',
                'pseudo_class' => $value .= $token->value,
                default => throw new SyntaxException(
                    sprintf('Unexpected token in selector: %s', $token->type),
                    $token->line,
                    $token->column
                ),
            };

            if ($token->type === 'attribute_selector') {
                $value = substr($value, 0, -strlen($token->value)) . $this->optimizeAttributeSelector($token->value);
            }

            if ($token->type === 'operator' && $token->value === ',') {
                $afterComma = true;
            }

            $this->advanceToken();
        }

        return new SelectorNode(trim($value), $line, $indent);
    }

    private function skipEmptyLines(): void
    {
        while ($this->currentToken()) {
            $token = $this->currentToken();

            if ($token->type === 'newline') {
                $this->advanceToken();
                continue;
            }

            if ($token->type === 'whitespace') {
                $next = $this->peekTokenAhead();
                if ($next && $next->type === 'newline') {
                    $this->advanceToken();
                    $this->advanceToken();
                    continue;
                }
            }

            break;
        }
    }

    private function peekTokenAhead(): ?Token
    {
        $position = $this->getTokenIndex() + 1;
        $tokens = $this->getTokens();

        return $tokens[$position] ?? null;
    }

    private function peekMatches(string ...$types): bool
    {
        $token = $this->currentToken();

        if ($token === null) {
            return false;
        }

        if (in_array($token->type, $types, true)) {
            return true;
        }

        return false;
    }
}
