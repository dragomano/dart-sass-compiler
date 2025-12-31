<?php

declare(strict_types=1);

namespace DartSass\Parsers;

use DartSass\Exceptions\SyntaxException;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\CssCustomPropertyNode;
use DartSass\Parsers\Nodes\CssPropertyNode;
use DartSass\Parsers\Nodes\FunctionNode;
use DartSass\Parsers\Nodes\HexColorNode;
use DartSass\Parsers\Nodes\IdentifierNode;
use DartSass\Parsers\Nodes\IncludeNode;
use DartSass\Parsers\Nodes\InterpolationNode;
use DartSass\Parsers\Nodes\ListNode;
use DartSass\Parsers\Nodes\MixinNode;
use DartSass\Parsers\Nodes\NullNode;
use DartSass\Parsers\Nodes\NumberNode;
use DartSass\Parsers\Nodes\OperationNode;
use DartSass\Parsers\Nodes\OperatorNode;
use DartSass\Parsers\Nodes\PropertyAccessNode;
use DartSass\Parsers\Nodes\ReturnNode;
use DartSass\Parsers\Nodes\RuleNode;
use DartSass\Parsers\Nodes\SelectorNode;
use DartSass\Parsers\Nodes\StringNode;
use DartSass\Parsers\Nodes\UnaryNode;
use DartSass\Parsers\Nodes\VariableDeclarationNode;
use DartSass\Parsers\Nodes\VariableNode;
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

use function array_filter;
use function array_merge;
use function count;
use function is_array;
use function preg_match;
use function preg_replace;
use function sprintf;
use function str_ends_with;
use function str_starts_with;
use function trim;

class ScssParser implements TokenAwareParserInterface
{
    protected const UNARY_OPERATORS = [
        '-'   => true,
        '+'   => true,
        'not' => true,
    ];

    protected const NESTED_SELECTOR_OPERATORS = [
        '&' => true,
        '.' => true,
        '#' => true,
        '>' => true,
        '+' => true,
        '~' => true,
    ];

    private static ?string $attributeRegex1 = '/\[([^\]=]+)([~*^|$!]?=)(["\'"]?)([^"\'\s]+)(\3)\]/';

    private static ?string $attributeRegex2 = '/^[a-zA-Z0-9_-]+$/';

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

    private const OPERATOR_TYPES = [
        'operator'         => true,
        'logical_operator' => true,
        'colon'            => true,
        'asterisk'         => true,
    ];

    private const DECLARATION_VALUE_TYPES = [
        'identifier' => true,
        'number'     => true,
        'string'     => true,
        'hex_color'  => true,
        'function'   => true,
        'variable'   => true,
    ];

    private const IDENTIFIER_VARIABLE_TYPES = [
        'identifier' => true,
        'variable'   => true,
    ];

    private const BLOCK_END_TYPES = [
        'semicolon'   => true,
        'brace_close' => true,
        'brace_open'  => true,
    ];

    public function __construct(protected TokenStreamInterface $stream) {}

    /**
     * @throws SyntaxException
     */
    public function parse(): array
    {
        $rules = [];

        while (! $this->stream->isEnd()) {
            $token = $this->stream->current();

            if ($token === null) {
                break;
            }

            if ($token->type === 'at_rule') {
                if ($token->value === '@function') {
                    $rules[] = $this->parseFunction();
                } elseif ($token->value === '@mixin') {
                    $rules[] = $this->parseMixin();
                } elseif ($token->value === '@include') {
                    $rules[] = $this->parseInclude();
                } else {
                    $rules[] = $this->parseAtRule();
                }
            } elseif ($token->type === 'variable') {
                $rules[] = $this->parseVariable();
            } else {
                $rules[] = $this->parseRule();
            }
        }

        return $rules;
    }

    public function advanceToken(): void
    {
        $this->stream->advance();
    }

    public function consume(string $type): Token
    {
        return $this->stream->consume($type);
    }

    public function currentToken(): ?Token
    {
        return $this->stream->current();
    }

    public function getTokenIndex(): int
    {
        return $this->stream->getPosition();
    }

    public function getTokens(): array
    {
        return $this->stream->getTokens();
    }

    public function peek(string $type): bool
    {
        return $this->stream->matches($type);
    }

    public function setTokenIndex(int $index): void
    {
        $this->stream->setPosition($index);
    }

    public function parseAtRule(): AstNode
    {
        $parser = $this->createAtRuleParser();

        return $parser->parse();
    }

    /**
     * @throws SyntaxException
     */
    public function parseRule(): AstNode
    {
        $selector = $this->parseSelector();

        $this->stream->consume('brace_open');

        $block = $this->parseBlock();

        return new RuleNode(
            $selector,
            $block['declarations'],
            $block['nested'],
            $selector->properties['line'],
            $selector->column ?? 0
        );
    }

    /**
     * @throws SyntaxException
     */
    public function parseDeclaration(): array
    {
        $propertyToken = $this->stream->expectAny('identifier', 'css_custom_property');

        $property = $propertyToken->value;

        $this->stream->consume('colon');
        $this->stream->skipWhitespace();

        $value  = $this->parseExpression();
        $values = null;

        $hasMultipleValues = false;
        $isCommaSeparated  = false;

        while (
            $this->stream->current()
            && ! $this->stream->matchesAny('semicolon', 'brace_close')
        ) {
            if ($this->stream->consumeIf('whitespace')) {
                continue;
            }

            if (! $hasMultipleValues) {
                $hasMultipleValues = true;
                $values = [$value];
            }

            $token = $this->stream->current();
            if ($token && $token->type === 'operator' && $token->value === ',') {
                $isCommaSeparated = true;

                $this->stream->advance();
                $this->stream->skipWhitespace();

                $values[] = $this->parseExpression();
            } else {
                $nextToken = $this->stream->current();

                if ($nextToken && isset(self::DECLARATION_VALUE_TYPES[$nextToken->type])) {
                    if (isset(self::IDENTIFIER_VARIABLE_TYPES[$nextToken->type])) {
                        $values[] = $this->parseExpression();
                    } else {
                        $values[] = $this->parsePrimaryExpression();
                    }
                } else {
                    break;
                }
            }
        }

        if ($hasMultipleValues) {
            $separator = $isCommaSeparated ? 'comma' : 'space';
            $value = new ListNode($values, $value->properties['line'] ?? 0, $separator);
        }

        // Check for !important modifier using dedicated token
        $token = $this->stream->current();
        if ($token && $token->type === 'important_modifier') {
            $this->stream->consume('important_modifier');

            $value->properties['important'] = true;
        } elseif ($token && $token->type === 'operator' && $token->value === '!') {
            // Regular ! operator - just consume it
            $this->stream->consume('operator');
        }

        if (! $this->stream->consumeIf('semicolon')) {
            $this->stream->consumeIf('newline');
        }

        $value->properties['property_line']   = $propertyToken->line;
        $value->properties['property_column'] = $propertyToken->column;

        return [$property => $value];
    }

    /**
     * @throws SyntaxException
     */
    public function parseExpression(): AstNode
    {
        $left = $this->parseBinaryExpression(0);

        $this->stream->skipWhitespace();

        if ($left->type === 'property_access' && $this->stream->matches('semicolon')) {
            return $left;
        }

        if ($this->stream->matchesAny('brace_open', 'brace_close')) {
            return $left;
        }

        $token = $this->stream->current();
        if ($token === null) {
            return $left;
        }

        if ($token->type === 'identifier' && $token->value === 'null') {
            return $left;
        }

        if ($token->type === 'operator' && $token->value === ',') {
            $values = [$left];

            while ($this->stream->current()->type === 'operator' && $this->stream->current()->value === ',') {
                $this->stream->advance();
                $this->stream->skipWhitespace();

                $values[] = $this->parseBinaryExpression(0);
                $this->stream->skipWhitespace();

                if ($this->stream->matches('brace_open')) {
                    break;
                }
            }

            return new ListNode($values, $left->properties['line'] ?? 0, 'comma');
        }

        if ($token && $token->type === 'operator' && $token->value === '!') {
            return $left;
        }

        if (
            $token
            && ! isset(self::BLOCK_END_TYPES[$token->type])
            && ! ($token->type === 'operator' && $token->value === ',')
        ) {
            // Check for space-separated list
            $currentPos = $this->stream->getPosition();

            $this->stream->skipWhitespace();

            if (
                $this->stream->current()
                && ! isset(self::BLOCK_END_TYPES[$this->stream->current()->type])
                && ! ($this->stream->current()->type === 'operator' && $this->stream->current()->value === ',')
            ) {
                $nextToken = $this->stream->current();

                // Check if the next token is a control flow keyword (should not create list)
                if (
                    $nextToken && $nextToken->type === 'identifier'
                    && in_array($nextToken->value, ['to', 'through', 'from'], true)
                ) {
                    $this->stream->setPosition($currentPos);

                    return $left;
                }

                // Check if we have variable followed by colon (named parameter)
                if ($left->type === 'variable') {
                    if ($nextToken && $nextToken->type === 'colon') {
                        $this->stream->setPosition($currentPos);

                        return $left;
                    }

                }

                $values = [$left];

                while (
                    $this->stream->current()
                    && ! isset(self::BLOCK_END_TYPES[$this->stream->current()->type])
                    && $this->stream->current()->type !== 'paren_close'
                    && ! ($this->stream->current()->type === 'operator' && $this->stream->current()->value === ',')
                ) {
                    $this->stream->skipWhitespace();
                    if (
                        isset(self::BLOCK_END_TYPES[$this->stream->current()->type])
                        || $this->stream->current()->type === 'paren_close'
                        || ($this->stream->current()->type === 'operator' && $this->stream->current()->value === ',')
                    ) {
                        break;
                    }

                    $values[] = $this->parseBinaryExpression(0);
                    $this->stream->skipWhitespace();
                }

                if (count($values) > 1) {
                    return new ListNode($values, $left->properties['line'] ?? 0, 'space');
                }
            } else {
                $this->stream->setPosition($this->stream->getPosition() - 1);
            }
        }

        return $left;
    }

    /**
     * @throws SyntaxException
     */
    public function parseBlock(): array
    {
        $declarations = $nested = $items = [];

        while (($token = $this->stream->current()) && $token->type !== 'brace_close') {
            $this->stream->skipWhitespace();

            if (! $token = $this->stream->current()) {
                break;
            }

            if ($token->type === 'brace_close') {
                break;
            }

            $tokenType = $token->type;
            $item = null;

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
                    if (isset(self::NESTED_SELECTOR_OPERATORS[$tokenValue]) || $tokenValue === '[') {
                        $item = $this->parseRule();
                        $nested[] = $item;
                    } else {
                        $item = $this->parseDeclaration();
                        $declarations[] = $item;
                    }

                    break;

                case 'function':
                    // Check if this is a pseudo-class function (like :has, :not, etc.)
                    $isPseudoClass = $this->checkIfSelector();
                    if ($isPseudoClass) {
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
                    } else {
                        $this->stream->setPosition($savedPos);
                        $item = $this->parseRule();
                        $nested[] = $item;
                    }

                    break;

                default:
                    $item = $this->parseDeclaration();
                    $declarations[] = $item;

                    break;
            }

            $items[] = $item;
        }

        $this->stream->consume('brace_close');

        return [
            'declarations' => $declarations,
            'nested'       => $nested,
            'items'        => $items,
        ];
    }

    /**
     * @throws SyntaxException
     */
    public function parseInclude(): AstNode
    {
        $this->stream->consume('at_rule');
        $this->stream->skipWhitespace();

        $token = $this->stream->expectAny('identifier', 'function');
        $name = $token->value;

        while ($this->stream->current()->type === 'operator' && $this->stream->current()->value === '.') {
            $this->stream->consume('operator');

            $nextToken = $this->stream->expectAny('identifier', 'function');
            $name .= '.' . $nextToken->value;
        }

        $args = [];

        while (! $this->stream->matchesAny('semicolon', 'brace_open')) {
            $this->stream->skipWhitespace();

            if ($this->stream->matchesAny('semicolon', 'brace_open')) {
                break;
            }

            if ($this->stream->matches('paren_open')) {
                $this->stream->consume('paren_open');

                while (! $this->stream->matches('paren_close')) {
                    $this->stream->skipWhitespace();

                    if ($this->stream->matches('paren_close')) {
                        break;
                    }

                    $argValue = $this->parseExpression();
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
        } else {
            $this->stream->consume('semicolon');
        }

        return new IncludeNode($name, $args, $content);
    }

    /**
     * @throws SyntaxException
     */
    public function parseVariable(): AstNode
    {
        $token = $this->stream->consume('variable');
        $this->stream->consume('colon');

        $value = $this->parseExpression();

        $global  = false;
        $default = false;

        $current = $this->stream->current();
        if (
            $current
            && $current->type === 'operator'
            && $current->value === '!'
        ) {
            $this->stream->advance();

            if ($this->stream->matches('identifier')) {
                $flag = $this->stream->consume('identifier')->value;

                if ($flag === 'global') {
                    $global = true;
                } elseif ($flag === 'default') {
                    $default = true;
                }
            }
        }

        return $this->buildVariableNode($token, $value, $global, $default);
    }

    protected function parsePseudoClassFunction(Token $token): string
    {
        $result = $token->value;

        $this->stream->advance();
        $this->stream->consume('paren_open');

        $result .= '(';

        $parenLevel = 1;
        while ($this->stream->current() && $parenLevel > 0) {
            $current = $this->stream->current();

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

            $this->stream->advance();
        }

        $result .= ')';

        return $result;
    }

    protected function buildVariableNode(Token $token, AstNode $value, bool $global, bool $default): AstNode
    {
        $this->stream->consume('semicolon');

        return new VariableDeclarationNode(
            $token->value,
            $value,
            $token->line,
            $global,
            $default
        );
    }

    protected function optimizeAttributeSelector(string $selector): string
    {
        if (preg_match(self::$attributeRegex1, $selector, $matches)) {
            $attribute = $matches[1];
            $operator  = $matches[2];
            $value     = $matches[4];

            if (preg_match(self::$attributeRegex2, $value)) {
                return "[$attribute$operator$value]";
            }
        }

        return $selector;
    }

    /**
     * @throws SyntaxException
     */
    protected function parseFunction(): AstNode
    {
        [$name, $args] = $this->parseNameAndInit();

        $this->stream->consume('paren_open');

        while (! $this->stream->matches('paren_close')) {
            $this->stream->skipWhitespace();

            if ($this->stream->matches('paren_close')) {
                break;
            }

            $opToken = $this->stream->current();
            if ($opToken && $opToken->type === 'operator' && $opToken->value === ',') {
                $this->stream->advance();

                continue;
            }

            if ($this->stream->matches('variable')) {
                $argName = $this->stream->consume('variable')->value;

                if ($this->stream->matches('colon')) {
                    $this->stream->consume('colon');

                    $defaultValue = $this->parseBinaryExpression(0);
                    $args[$argName] = $defaultValue;
                } else {
                    $args[] = $argName;
                }
            } else {
                $args[] = $this->stream->consume('identifier')->value;
            }

            if (! $this->stream->matches('paren_close')) {
                $this->stream->skipWhitespace();

                $commaToken = $this->stream->current();
                if ($commaToken && $commaToken->type === 'operator' && $commaToken->value === ',') {
                    $this->stream->advance();
                }
            }
        }

        $this->stream->consume('paren_close');
        $this->stream->consume('brace_open');

        $content = $this->parseBody();

        return new FunctionNode($name, $args, $content);
    }

    /**
     * @throws SyntaxException
     */
    protected function parseReturn(): AstNode
    {
        $this->stream->consume('at_rule');

        $value = $this->parseExpression();

        $this->stream->consumeIf('semicolon');

        return new ReturnNode($value, 0);
    }

    /**
     * @throws SyntaxException
     */
    protected function parseMixin(): AstNode
    {
        [$name, $args] = $this->parseNameAndInit();

        while (! $this->stream->matches('brace_open')) {
            $this->stream->skipWhitespace();

            if ($this->stream->matches('brace_open')) {
                break;
            }

            if ($this->stream->matches('paren_open')) {
                $this->stream->consume('paren_open');

                while (! $this->stream->matches('paren_close')) {
                    $this->stream->skipWhitespace();

                    if ($this->stream->matches('paren_close')) {
                        break;
                    }

                    if ($this->stream->matches('variable')) {
                        $varToken = $this->stream->consume('variable');
                        $argName  = $varToken->value;

                        if ($this->stream->matches('colon')) {
                            $this->stream->consume('colon');

                            $defaultValue = $this->parseBinaryExpression(0);
                            $args[$argName] = $defaultValue;
                        } else {
                            $args[$argName] = new NullNode($varToken->line);
                        }
                    } else {
                        $this->stream->consume('identifier');
                    }

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

        $this->stream->consume('brace_open');

        $content = $this->parseBody();

        $content = array_filter($content, function ($node) {
            if (is_array($node)) {
                return ! empty($node);
            }

            return $node && isset($node->type) && $node->type !== '';
        });

        return new MixinNode($name, $args, $content);
    }

    /**
     * @throws SyntaxException
     */
    protected function parseBinaryExpression(int $minPrecedence): AstNode
    {
        $this->stream->skipWhitespace();

        $token = $this->stream->current();
        if ($token && (isset(self::UNARY_OPERATORS[$token->value]) || $token->type === 'unary_operator')) {
            $operator = $token->value;
            $line = $token->line;
            $this->stream->advance();
            $operand = $this->parseBinaryExpression(5);

            return new UnaryNode($operator, $operand, $line);
        }

        $left = $this->parsePrimaryExpression();

        $this->stream->skipWhitespace();

        while (true) {
            $token = $this->stream->current();
            if (! $token || ! isset(self::OPERATOR_TYPES[$token->type]) || isset(self::BLOCK_END_TYPES[$token->type])) {
                break;
            }

            $operator = $token->value;

            $tokensToSkip = 1;

            $nextValue = $this->stream->peekValue();
            if ($operator === '=' && $nextValue === '=') {
                $operator = '==';
                $tokensToSkip = 2;
            } elseif ($operator === '!' && $nextValue === '=') {
                $operator = '!=';
                $tokensToSkip = 2;
            } elseif ($operator === '<' && $nextValue === '=') {
                $operator = '<=';
                $tokensToSkip = 2;
            } elseif ($operator === '>' && $nextValue === '=') {
                $operator = '>=';
                $tokensToSkip = 2;
            }

            if ($operator === '.') {
                $this->stream->advance();

                $right = $this->parsePrimaryExpression();

                $this->stream->skipWhitespace();

                $left = new PropertyAccessNode($left, $right, $token->line);

                $nextToken = $this->stream->current();
                if ($nextToken && isset(self::BLOCK_END_TYPES[$nextToken->type])) {
                    return $left;
                }

                continue;
            }

            $precedence = $this->getOperatorPrecedence($operator);

            if ($precedence <= $minPrecedence) {
                break;
            }

            for ($i = 0; $i < $tokensToSkip; $i++) {
                $this->stream->advance();
            }

            $this->stream->skipWhitespace();

            $right = $this->parseBinaryExpression($precedence + 1);
            $left  = new OperationNode($left, $operator, $right, $token->line);
        }

        return $left;
    }

    protected function checkIfSelector(): bool
    {
        if ($this->stream->matches('function')) {
            $parenLevel = 1;
            $this->stream->advance();

            if ($this->stream->matches('paren_open')) {
                $this->stream->consume('paren_open');

                while ($this->stream->current() && $parenLevel > 0) {
                    if ($this->stream->matches('paren_open')) {
                        $parenLevel++;
                    } elseif ($this->stream->matches('paren_close')) {
                        $parenLevel--;
                    }

                    $this->stream->advance();
                }
            }

            $this->stream->skipWhitespace();

            return $this->stream->matches('brace_open');
        }

        if ($this->stream->matches('identifier')) {
            $this->stream->advance();
            $this->stream->skipWhitespace();

            return $this->stream->matches('brace_open');
        }

        return false;
    }

    /**
     * @throws SyntaxException
     */
    protected function parseBody(): array
    {
        $body = $this->parseBlock();

        return $body['items'] ?? array_merge($body['declarations'], $body['nested']);
    }

    /**
     * @throws SyntaxException
     */
    private function parseSelector(): AstNode
    {
        $value = '';
        $line = $this->stream->current()->line;

        while (
            ($token = $this->stream->current())
            && ! $this->stream->matchesAny('brace_open', 'semicolon')
        ) {
            if (isset(self::SELECTOR_TOKENS[$token->type])) {
                $value .= $token->value;
                $this->stream->advance();
            } elseif ($token->type === 'function') {
                $value .= $this->parsePseudoClassFunction($token);
            } elseif ($token->type === 'operator') {
                $value .= $token->value;
                $this->stream->advance();
            } elseif ($token->type === 'double_hash_interpolation') {
                $this->stream->advance();

                $variableToken = $this->stream->consume('variable');
                $this->stream->consume('brace_close');

                $value .= '#' . $variableToken->value;
            } elseif ($token->type === 'attribute_selector') {
                $value .= $this->optimizeAttributeSelector($token->value);
                $this->stream->advance();
            } elseif ($token->type === 'interpolation_open') {
                $value .= $this->parseInterpolationInSelector();
            } elseif ($token->type === 'at_rule') {
                if ($token->value === '@content') {
                    $value .= '@content';
                    $this->stream->advance();
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

        return new SelectorNode($value, $line);
    }

    /**
     * @throws SyntaxException
     */
    private function parseCssPropertyValue(): AstNode
    {
        $propertyToken = $this->stream->consume('identifier');
        $this->stream->consume('colon');

        $valueExpression = $this->parseBinaryExpression(0);

        return new CssPropertyNode($propertyToken->value, $valueExpression, $propertyToken->line);
    }

    /**
     * @throws SyntaxException
     */
    private function parsePrimaryExpression(): AstNode
    {
        $token = $this->stream->current();

        if ($token === null) {
            throw new SyntaxException('Unexpected end of input', 0, 0);
        }

        $this->stream->advance();

        return match ($token->type) {
            'number' => $this->parseNumber($token),

            'identifier' => (function () use ($token) {
                if ($this->stream->matches('colon')) {
                    $this->stream->setPosition($this->stream->getPosition() - 1);

                    return $this->parseCssPropertyValue();
                }

                return new IdentifierNode($token->value, $token->line);
            })(),

            'hex_color' => new HexColorNode($token->value, $token->line),

            'string' => new StringNode(trim($token->value, '"\''), $token->line),

            'variable' => new VariableNode($token->value, $token->line),

            'css_custom_property' => new CssCustomPropertyNode($token->value, $token->line),

            'url_function' => (function () use ($token) {
                $fullContent = preg_replace('/^url\((.*)\)$/s', '$1', $token->value);
                $fullContent = trim($fullContent);

                // Pass the original content to UrlFunctionHandler for quote detection
                $urlNode = new StringNode($fullContent, $token->line);

                return new FunctionNode('url', [$urlNode], line: $token->line);
            })(),

            'function' => (function () use ($token) {
                $funcName = $token->value;

                $this->stream->consume('paren_open');

                $args = [];

                while (! $this->stream->matches('paren_close')) {
                    $this->stream->skipWhitespace();

                    if ($this->stream->matches('paren_close')) {
                        break;
                    }

                    if ($this->stream->matches('variable')) {
                        $varToken = $this->stream->consume('variable');

                        $argName = $varToken->value;

                        if ($this->stream->matches('colon')) {
                            $this->stream->consume('colon');
                            $this->stream->skipWhitespace();

                            $argValue = $this->parseBinaryExpression(0);

                            $args[$argName] = $argValue;
                        } else {
                            $this->stream->setPosition($this->stream->getPosition() - 1);

                            $arg = $this->parseBinaryExpression(0);
                            $args[] = $arg;
                        }
                    } else {
                        $arg = $this->parseBinaryExpression(0);
                        $args[] = $arg;
                    }

                    if (! $this->stream->matches('paren_close')) {
                        $this->stream->skipWhitespace();

                        $commaToken = $this->stream->current();
                        if ($commaToken && $commaToken->type === 'operator' && $commaToken->value === ',') {
                            $this->stream->advance();
                        }
                    }
                }

                $this->stream->consume('paren_close');

                if ($funcName === 'linear-gradient' && count($args) >= 2) {
                    $formattedArgs = array_map($this->formatExpressionForSelector(...), $args);
                    if ($formattedArgs[0] === 'to' && $formattedArgs[1] === 'bottom') {
                        $mergedArgs = ['to bottom'];
                        for ($i = 2; $i < count($args); $i++) {
                            $mergedArgs[] = $args[$i];
                        }

                        $args = $mergedArgs;
                    } elseif ($formattedArgs[0] === 'to' && $formattedArgs[1] === 'right') {
                        $mergedArgs = ['to right'];
                        for ($i = 2; $i < count($args); $i++) {
                            $mergedArgs[] = $args[$i];
                        }

                        $args = $mergedArgs;
                    }
                }

                return new FunctionNode($funcName, $args, line: $token->line);
            })(),

            'paren_open' => (function () use ($token) {
                $this->stream->skipWhitespace();

                if ($this->stream->matches('paren_close')) {
                    $this->stream->consume('paren_close');

                    return new ListNode([], $token->line, 'space');
                }

                $node = $this->parseExpression();

                $this->stream->consume('paren_close');

                return $node;
            })(),

            'interpolation_open' => (function () use ($token) {
                $expression = $this->parseExpression();

                $this->stream->consume('brace_close');

                return new InterpolationNode($expression, $token->line);
            })(),

            'attribute_selector' => (function () use ($token) {
                $value  = trim($token->value, '[]');
                $parts  = array_map(trim(...), explode(',', $value));
                $values = [];

                foreach ($parts as $part) {
                    if (preg_match('/^(\d+(?:\.\d+)?)(px|em|rem|%)?$/', $part, $matches)) {
                        $values[] = $matches[2]
                            ? ['value' => (float) $matches[1], 'unit' => $matches[2]]
                            : (float) $matches[1];
                    } else {
                        $values[] = $part;
                    }
                }

                return new ListNode($values, $token->line, 'comma', true);
            })(),

            'operator', 'asterisk', 'colon', 'semicolon' => new OperatorNode($token->value, $token->line),

            'important_modifier' => new IdentifierNode('!important', $token->line),

            default => (function () use ($token): void {
                throw new SyntaxException(
                    sprintf('Unexpected token in expression: %s', $token->type),
                    $token->line,
                    $token->column
                );
            })(),
        };
    }

    private function parseNameAndInit(): array
    {
        $this->stream->consume('at_rule');
        $this->stream->skipWhitespace();

        $token = $this->stream->expectAny('identifier', 'function');
        $name = $token->value;
        $args = [];

        return [$name, $args];
    }

    private function getOperatorPrecedence(string $operator): int
    {
        return match ($operator) {
            'or'                 => 1,
            'and'                => 2,
            '+', '-'             => 3,
            '*', '/', '%'        => 4,
            '==', '!='           => 5,
            '<', '>', '<=', '>=' => 6,
            default              => 0,
        };
    }

    private function createAtRuleParser(): AtRuleParser
    {
        $token = $this->stream->current();
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
    private function parseInterpolationInSelector(): string
    {
        $this->stream->consume('interpolation_open');

        $expression = $this->parseExpression();

        $this->stream->consume('brace_close');

        return '#{' . $this->formatExpressionForSelector($expression) . '}';
    }

    private function formatExpressionForSelector(AstNode $expr): string
    {
        if ($expr->type === 'variable') {
            return $expr->properties['name'];
        }

        if ($expr->type === 'identifier') {
            return $expr->properties['value'];
        }

        if ($expr->type === 'number') {
            return $expr->properties['value'];
        }

        if ($expr->type === 'string') {
            $value = $expr->properties['value'];

            if (str_starts_with((string) $value, '"') && str_ends_with((string) $value, '"')) {
                $value = trim((string) $value, '"');
            }

            return $value;
        }

        if ($expr->type === 'interpolation') {
            return '#{' . $this->formatExpressionForSelector($expr->properties['expression']) . '}';
        }

        return 'expression';
    }

    private function parseNumber(Token $token): AstNode
    {
        $valueStr = $token->value;

        if (preg_match('/^(-?\d*\.?\d+)(.*)$/', $valueStr, $matches)) {
            $value = (float) $matches[1];
            $unit  = trim($matches[2]) ?: null;
        } else {
            $value = 0.0;
            $unit  = null;
        }

        return new NumberNode($value, $token->line, $unit);
    }
}
