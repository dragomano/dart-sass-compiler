<?php

declare(strict_types=1);

namespace DartSass\Parsers;

use DartSass\Exceptions\SyntaxException;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\CommentNode;
use DartSass\Parsers\Nodes\IncludeNode;
use DartSass\Parsers\Nodes\ListNode;
use DartSass\Parsers\Nodes\ReturnNode;
use DartSass\Parsers\Nodes\RuleNode;
use DartSass\Parsers\Nodes\VariableDeclarationNode;
use DartSass\Parsers\Rules\AtRuleParser;
use DartSass\Parsers\Rules\ContainerRuleParser;
use DartSass\Parsers\Rules\EachRuleParser;
use DartSass\Parsers\Rules\ForRuleParser;
use DartSass\Parsers\Rules\ForwardRuleParser;
use DartSass\Parsers\Rules\GenericAtRuleParser;
use DartSass\Parsers\Rules\IfRuleParser;
use DartSass\Parsers\Rules\KeyframesRuleParser;
use DartSass\Parsers\Rules\MediaRuleParser;
use DartSass\Parsers\Rules\UseRuleParser;
use DartSass\Parsers\Rules\WhileRuleParser;
use DartSass\Parsers\Tokens\Token;
use DartSass\Utils\StringFormatter;

use function array_merge;

class BlockParser extends AbstractParser
{
    protected const DECLARATION_VALUE_TYPES = [
        'identifier' => true,
        'number'     => true,
        'string'     => true,
        'hex_color'  => true,
        'function'   => true,
        'variable'   => true,
    ];

    protected const IDENTIFIER_VARIABLE_TYPES = [
        'identifier' => true,
        'variable'   => true,
    ];

    protected const NESTED_SELECTOR_OPERATORS = [
        '&' => true,
        '.' => true,
        '#' => true,
        '>' => true,
        '+' => true,
        '~' => true,
    ];

    /**
     * @throws SyntaxException
     */
    public function parse(): array
    {
        return $this->parseBlock();
    }

    /**
     * @throws SyntaxException
     */
    public function parseBlock(): array
    {
        $declarations = $nested = $items = [];

        while (($token = $this->currentToken()) && $token->type !== 'brace_close') {
            if ($token->type === 'whitespace') {
                $this->skipWhitespace();

                $token = $this->currentToken();
                if (! $token || $token->type === 'brace_close') {
                    break;
                }
            }

            $item = $this->parseBlockItem($token, $declarations, $nested);

            $items[] = $item;
        }

        $this->consume('brace_close');

        return [
            'declarations' => $declarations,
            'nested'       => $nested,
            'items'        => $items,
        ];
    }

    /**
     * @throws SyntaxException
     */
    public function parseDeclaration(): array
    {
        $propertyToken = $this->expectAny('identifier', 'css_custom_property');

        $property = $propertyToken->value;

        $this->consume('colon');
        $this->skipWhitespace();

        $value = $this->parseDeclarationValue();

        $this->handleImportantModifier($value);
        $this->consumeDeclarationTerminator();

        $value->line   = $propertyToken->line;
        $value->column = $propertyToken->column;

        return [$property => $value];
    }

    /**
     * @throws SyntaxException
     */
    public function parseRule(): AstNode
    {
        $selector = $this->parseSelector();

        $this->consume('brace_open');

        $block = $this->parseBlock();

        return new RuleNode(
            $selector,
            $block['declarations'],
            $block['nested'],
            $selector->line,
            $selector->column
        );
    }

    /**
     * @throws SyntaxException
     */
    public function parseExpression(): AstNode
    {
        $parser = new ExpressionParser($this->getStream());

        return $parser->parse();
    }

    /**
     * @throws SyntaxException
     */
    public function parseInclude(): AstNode
    {
        $this->consume('at_rule');
        $this->skipWhitespace();

        $name = $this->parseIncludeName();
        $args = $this->parseIncludeArguments();

        $this->skipWhitespace();

        $content = $this->parseIncludeContent();

        return new IncludeNode($name, $args, $content);
    }

    public function parseAtRule(): AstNode
    {
        $parser = $this->createAtRuleParser();

        return $parser->parse();
    }

    /**
     * @throws SyntaxException
     */
    public function parseVariable(): AstNode
    {
        $token = $this->consume('variable');

        $this->consume('colon');

        $value = $this->parseExpression();

        [$global, $default] = $this->parseVariableFlags();

        $this->consume('semicolon');

        return new VariableDeclarationNode(
            $token->value,
            $value,
            $global,
            $default,
            $token->line,
        );
    }

    /**
     * @throws SyntaxException
     */
    private function parseBlockItem(Token $token, array &$declarations, array &$nested): AstNode|array
    {
        return match ($token->type) {
            'selector',
            'asterisk',
            'attribute_selector' => $this->handleRule($nested),
            'at_rule'            => $this->handleAtRule($token, $nested),
            'variable'           => $this->handleVariable($nested),
            'comment'            => $this->handleComment($token, $declarations),
            'operator'           => $this->handleOperator($token, $declarations, $nested),
            'function'           => $this->handleFunction($declarations, $nested),
            'identifier'         => $this->handleIdentifier($declarations, $nested),
            default              => $this->handleDeclaration($declarations),
        };
    }

    /**
     * @throws SyntaxException
     */
    private function handleAtRule(Token $token, array &$nested): AstNode
    {
        $item = match ($token->value) {
            '@return'  => $this->parseReturn(),
            '@include' => $this->parseInclude(),
            default    => $this->parseAtRule(),
        };

        $nested[] = $item;

        return $item;
    }

    /**
     * @throws SyntaxException
     */
    private function handleVariable(array &$nested): AstNode
    {
        $item = $this->parseVariable();

        $nested[] = $item;

        return $item;
    }

    /**
     * @throws SyntaxException
     */
    private function handleRule(array &$nested): AstNode
    {
        $item = $this->parseRule();

        $nested[] = $item;

        return $item;
    }

    private function handleComment(Token $token, array &$declarations): AstNode
    {
        $item = new CommentNode($token->value, $token->line, $token->column);

        $declarations[] = $item;

        $this->advanceToken();

        return $item;
    }

    /**
     * @throws SyntaxException
     */
    private function handleOperator(Token $token, array &$declarations, array &$nested): AstNode|array
    {
        $tokenValue = $token->value;

        if (isset(self::NESTED_SELECTOR_OPERATORS[$tokenValue]) || $tokenValue === '[') {
            return $this->handleRule($nested);
        }

        return $this->handleDeclaration($declarations);
    }

    /**
     * @throws SyntaxException
     */
    private function handleFunction(array &$declarations, array &$nested): AstNode|array
    {
        if ($this->checkIfSelector()) {
            return $this->handleRule($nested);
        }

        return $this->handleDeclaration($declarations);
    }

    /**
     * @throws SyntaxException
     */
    private function handleIdentifier(array &$declarations, array &$nested): AstNode|array
    {
        $savedPos = $this->getTokenIndex();

        $this->advanceToken();
        $this->skipWhitespace();

        if ($this->currentToken()?->type === 'colon') {
            $this->advanceToken();
            $this->skipWhitespace();

            $isSelector = $this->checkIfSelector();

            $this->setTokenIndex($savedPos);

            if ($isSelector) {
                return $this->handleRule($nested);
            }

            return $this->handleDeclaration($declarations);
        }

        $this->setTokenIndex($savedPos);

        return $this->handleRule($nested);
    }

    /**
     * @throws SyntaxException
     */
    private function handleDeclaration(array &$declarations): array
    {
        $item = $this->parseDeclaration();

        $declarations[] = $item;

        return $item;
    }

    /**
     * @throws SyntaxException
     */
    private function parseDeclarationValue(): AstNode
    {
        $value  = $this->parseExpression();
        $values = null;

        $hasMultipleValues = false;
        $isCommaSeparated  = false;

        while ($this->currentToken() && ! $this->matchesAny('semicolon', 'brace_close')) {
            if ($this->consumeIf('whitespace')) {
                continue;
            }

            if (! $hasMultipleValues) {
                $hasMultipleValues = true;

                $values = [$value];
            }

            $token = $this->currentToken();

            if ($token && $token->type === 'operator' && $token->value === ',') {
                $isCommaSeparated = true;

                $this->advanceToken();
                $this->skipWhitespace();

                $values[] = $this->parseExpression();
            } elseif ($token && isset(self::DECLARATION_VALUE_TYPES[$token->type])) {
                if (isset(self::IDENTIFIER_VARIABLE_TYPES[$token->type])) {
                    $values[] = $this->parseExpression();
                } else {
                    $values[] = $this->parsePrimaryExpression();
                }
            } else {
                break;
            }
        }

        if ($hasMultipleValues) {
            $separator = $isCommaSeparated ? 'comma' : 'space';
            $value     = new ListNode($values, $separator, line: $value->line ?? 0);
        }

        return $value;
    }

    private function handleImportantModifier(AstNode $value): void
    {
        $token = $this->currentToken();

        if ($token && $token->type === 'important_modifier') {
            $this->consume('important_modifier');

            $value->important = true;
        } elseif ($token && $token->type === 'operator' && $token->value === '!') {
            $this->consume('operator');
        }
    }

    private function consumeDeclarationTerminator(): void
    {
        if (! $this->consumeIf('semicolon')) {
            $this->consumeIf('newline');
        }
    }

    /**
     * @throws SyntaxException
     */
    private function parsePrimaryExpression(): AstNode
    {
        $parser = new ExpressionParser($this->getStream());

        return $parser->parsePrimaryExpression();
    }

    /**
     * @throws SyntaxException
     */
    private function parseSelector(): AstNode
    {
        $parser = new SelectorParser($this->getStream());

        return $parser->parse();
    }

    /**
     * @throws SyntaxException
     */
    private function parseReturn(): AstNode
    {
        $this->consume('at_rule');

        $value = $this->parseExpression();

        $this->consumeIf('semicolon');

        return new ReturnNode($value, 0);
    }

    private function parseIncludeName(): string
    {
        $token = $this->expectAny('identifier', 'function');
        $name  = $token->value;

        while ($this->currentToken()->type === 'operator' && $this->currentToken()->value === '.') {
            $this->consume('operator');

            $nextToken = $this->expectAny('identifier', 'function');

            $name = StringFormatter::concat($name, StringFormatter::concat('.', $nextToken->value));
        }

        return $name;
    }

    /**
     * @throws SyntaxException
     */
    private function parseIncludeArguments(): array
    {
        $args = [];

        while (! $this->matchesAny('semicolon', 'brace_open')) {
            $this->skipWhitespace();

            if ($this->matchesAny('semicolon', 'brace_open')) {
                break;
            }

            if ($this->peek('paren_open')) {
                $this->consume('paren_open');

                $args = $this->parseArgumentList();

                $this->consume('paren_close');

                break;
            }

            $this->advanceToken();
        }

        return $args;
    }

    /**
     * @throws SyntaxException
     */
    private function parseIncludeContent(): ?array
    {
        if ($this->peek('brace_open')) {
            $this->consume('brace_open');

            return $this->parseBody();
        }

        $this->consume('semicolon');

        return null;
    }

    private function parseVariableFlags(): array
    {
        $global  = false;
        $default = false;
        $current = $this->currentToken();

        if ($current && $current->type === 'operator' && $current->value === '!') {
            $this->advanceToken();

            if ($this->peek('identifier')) {
                $flag = $this->consume('identifier')->value;

                if ($flag === 'global') {
                    $global = true;
                } elseif ($flag === 'default') {
                    $default = true;
                }
            }
        }

        return [$global, $default];
    }

    private function createAtRuleParser(): AtRuleParser
    {
        $token    = $this->currentToken();
        $ruleType = $token->value;

        return match ($ruleType) {
            '@use'       => new UseRuleParser($this),
            '@forward'   => new ForwardRuleParser($this),
            '@for'       => new ForRuleParser($this),
            '@while'     => new WhileRuleParser($this),
            '@if'        => new IfRuleParser($this),
            '@each'      => new EachRuleParser($this),
            '@media'     => new MediaRuleParser($this),
            '@container' => new ContainerRuleParser($this),
            '@keyframes' => new KeyframesRuleParser($this),
            default      => new GenericAtRuleParser($this),
        };
    }

    /**
     * @throws SyntaxException
     */
    private function parseArgumentList(): array
    {
        $parser = new ExpressionParser($this->getStream());

        return $parser->parseArgumentList();
    }

    /**
     * @throws SyntaxException
     */
    private function parseBody(): array
    {
        $body = $this->parseBlock();

        return $body['items'] ?? array_merge($body['declarations'], $body['nested']);
    }

    private function checkIfSelector(): bool
    {
        if ($this->peek('function')) {
            $this->advanceToken();

            if ($this->peek('paren_open')) {
                $this->skipParentheses();
            }

            $this->skipWhitespace();

            return $this->peek('brace_open');
        }

        if ($this->peek('identifier')) {
            $this->advanceToken();
            $this->skipWhitespace();

            return $this->peek('brace_open');
        }

        return false;
    }

    private function skipParentheses(): void
    {
        $parenLevel = 1;

        $this->consume('paren_open');

        while ($this->currentToken() && $parenLevel > 0) {
            if ($this->peek('paren_open')) {
                $parenLevel++;
            } elseif ($this->peek('paren_close')) {
                $parenLevel--;
            }

            $this->advanceToken();
        }
    }
}
