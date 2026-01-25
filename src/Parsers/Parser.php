<?php

declare(strict_types=1);

namespace DartSass\Parsers;

use DartSass\Exceptions\SyntaxException;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\AtRuleNode;
use DartSass\Parsers\Nodes\ColorNode;
use DartSass\Parsers\Nodes\CommentNode;
use DartSass\Parsers\Nodes\CssCustomPropertyNode;
use DartSass\Parsers\Nodes\CssPropertyNode;
use DartSass\Parsers\Nodes\FunctionNode;
use DartSass\Parsers\Nodes\HexColorNode;
use DartSass\Parsers\Nodes\IdentifierNode;
use DartSass\Parsers\Nodes\IncludeNode;
use DartSass\Parsers\Nodes\InterpolationNode;
use DartSass\Parsers\Nodes\ListNode;
use DartSass\Parsers\Nodes\MapNode;
use DartSass\Parsers\Nodes\MixinNode;
use DartSass\Parsers\Nodes\NodeType;
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
use DartSass\Utils\StringFormatter;

use function array_filter;
use function array_merge;
use function count;
use function in_array;
use function is_array;
use function preg_match;
use function preg_replace;
use function sprintf;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function trim;

class Parser implements TokenAwareParserInterface
{
    protected const UNARY_OPERATORS = [
        '-'   => true,
        '+'   => true,
        'not' => true,
    ];

    protected const COLOR_FUNCTIONS = [
        'lch'   => true,
        'oklch' => true,
        'hsl'   => true,
        'hwb'   => true,
        'lab'   => true,
    ];

    protected const NESTED_SELECTOR_OPERATORS = [
        '&' => true,
        '.' => true,
        '#' => true,
        '>' => true,
        '+' => true,
        '~' => true,
    ];

    private static ?string $attributeRegex1 = '/\[([^\]=]+)([~*^|$!]?=)(["\']?)([^"\'\s]+)(\3)\]/';

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

            switch ($token->type) {
                case 'at_rule':
                    $rules[] = match ($token->value) {
                        '@function' => $this->parseFunction(),
                        '@mixin'    => $this->parseMixin(),
                        '@include'  => $this->parseInclude(),
                        '@import'   => $this->parseImport(),
                        default     => $this->parseAtRule(),
                    };

                    break;

                case 'variable':
                    $rules[] = $this->parseVariable();

                    break;

                case 'comment':
                    $rules[] = new CommentNode($token->value, $token->line, $token->column);

                    $this->advanceToken();

                    break;

                case 'whitespace':
                    $this->advanceToken();

                    break;

                default:
                    $this->skipWhitespace();

                    $rules[] = $this->parseRule();

                    break;
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

    public function skipWhitespace(): void
    {
        $this->stream->skipWhitespace();
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
    public function parseDeclaration(): array
    {
        $propertyToken = $this->stream->expectAny('identifier', 'css_custom_property');

        $property = $propertyToken->value;

        $this->consume('colon');
        $this->skipWhitespace();

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

                $this->advanceToken();
                $this->skipWhitespace();

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
            $value     = new ListNode($values, $separator, line: $value->line ?? 0);
        }

        // Check for !important modifier using dedicated token
        $token = $this->stream->current();
        if ($token && $token->type === 'important_modifier') {
            $this->consume('important_modifier');

            $value->important = true;
        } elseif ($token && $token->type === 'operator' && $token->value === '!') {
            // Regular ! operator - just consume it
            $this->consume('operator');
        }

        if (! $this->stream->consumeIf('semicolon')) {
            $this->stream->consumeIf('newline');
        }

        $value->line   = $propertyToken->line;
        $value->column = $propertyToken->column;

        return [$property => $value];
    }

    /**
     * @throws SyntaxException
     */
    public function parseExpression(): AstNode
    {
        $left = $this->parseBinaryExpression(0);

        $this->skipWhitespace();

        if ($left->type === NodeType::PROPERTY_ACCESS && $this->peek('semicolon')) {
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
                $this->advanceToken();
                $this->skipWhitespace();

                $values[] = $this->parseBinaryExpression(0);
                $this->skipWhitespace();

                if ($this->peek('brace_open')) {
                    break;
                }
            }

            return new ListNode($values, line: $left->line ?? 0);
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
            $currentPos = $this->getTokenIndex();

            $this->skipWhitespace();

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
                    $this->setTokenIndex($currentPos);

                    return $left;
                }

                // Check if we have variable followed by colon (named parameter)
                if ($left->type === NodeType::VARIABLE) {
                    if ($nextToken && $nextToken->type === 'colon') {
                        $this->setTokenIndex($currentPos);

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
                    $this->skipWhitespace();

                    if (
                        isset(self::BLOCK_END_TYPES[$this->stream->current()->type])
                        || $this->stream->current()->type === 'paren_close'
                        || ($this->stream->current()->type === 'operator' && $this->stream->current()->value === ',')
                    ) {
                        break;
                    }

                    $values[] = $this->parseBinaryExpression(0);

                    $this->skipWhitespace();
                }

                if (count($values) > 1) {
                    return new ListNode($values, 'space', line: $left->line ?? 0);
                }
            } else {
                $this->setTokenIndex($this->getTokenIndex() - 1);
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
            // Skip whitespace at the start of each iteration
            if ($token->type === 'whitespace') {
                $this->skipWhitespace();

                $token = $this->stream->current();
                if (! $token || $token->type === 'brace_close') {
                    break;
                }
            }

            $item = null;

            $tokenType = $token->type;

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

                case 'comment':
                    $item = new CommentNode($token->value, $token->line, $token->column);

                    $declarations[] = $item;

                    $this->advanceToken();

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
                    $savedPos = $this->getTokenIndex();

                    $this->advanceToken();
                    $this->skipWhitespace();

                    if ($this->stream->current()?->type === 'colon') {
                        $this->advanceToken();
                        $this->skipWhitespace();

                        $isSelector = $this->checkIfSelector();
                        $this->setTokenIndex($savedPos);

                        if ($isSelector) {
                            $item = $this->parseRule();
                            $nested[] = $item;
                        } else {
                            $item = $this->parseDeclaration();
                            $declarations[] = $item;
                        }
                    } else {
                        $this->setTokenIndex($savedPos);
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
    public function parseInclude(): AstNode
    {
        $this->consume('at_rule');
        $this->skipWhitespace();

        $token = $this->stream->expectAny('identifier', 'function');
        $name  = $token->value;

        while ($this->stream->current()->type === 'operator' && $this->stream->current()->value === '.') {
            $this->consume('operator');

            $nextToken = $this->stream->expectAny('identifier', 'function');

            $name = StringFormatter::concat($name, StringFormatter::concat('.', $nextToken->value));
        }

        $args = [];

        while (! $this->stream->matchesAny('semicolon', 'brace_open')) {
            $this->skipWhitespace();

            if ($this->stream->matchesAny('semicolon', 'brace_open')) {
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

        $this->skipWhitespace();

        $content = null;
        if ($this->peek('brace_open')) {
            $this->consume('brace_open');

            $content = $this->parseBody();
        } else {
            $this->consume('semicolon');
        }

        return new IncludeNode($name, $args, $content);
    }

    /**
     * @throws SyntaxException
     */
    public function parseVariable(): AstNode
    {
        $token = $this->consume('variable');

        $this->consume('colon');

        $value = $this->parseExpression();

        $global  = false;
        $default = false;

        $current = $this->stream->current();
        if (
            $current
            && $current->type === 'operator'
            && $current->value === '!'
        ) {
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

        return $this->buildVariableNode($token, $value, $global, $default);
    }

    protected function parsePseudoClassFunction(Token $token): string
    {
        $result = $token->value;

        $this->advanceToken();
        $this->consume('paren_open');

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

            $this->advanceToken();
        }

        $result .= ')';

        return $result;
    }

    protected function buildVariableNode(Token $token, AstNode $value, bool $global, bool $default): AstNode
    {
        $this->consume('semicolon');

        return new VariableDeclarationNode(
            $token->value,
            $value,
            $global,
            $default,
            $token->line,
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

        $this->consume('paren_open');

        while (! $this->peek('paren_close')) {
            $this->skipWhitespace();

            if ($this->peek('paren_close')) {
                break;
            }

            $opToken = $this->stream->current();
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
                            $this->stream->current()->line,
                            $this->stream->current()->column
                        );
                    }
                }

                if ($this->peek('colon')) {
                    $this->consume('colon');

                    $defaultValue = $this->parseBinaryExpression(0);
                    $args[] = ['name' => $argName, 'arbitrary' => $arbitrary, 'default' => $defaultValue];
                } else {
                    $args[] = ['name' => $argName, 'arbitrary' => $arbitrary];
                }
            } else {
                $argName = $this->consume('identifier')->value;
                $args[] = ['name' => $argName, 'arbitrary' => false];
            }

            if (! $this->peek('paren_close')) {
                $this->skipWhitespace();

                $commaToken = $this->stream->current();
                if ($commaToken && $commaToken->type === 'operator' && $commaToken->value === ',') {
                    $this->advanceToken();
                }
            }
        }

        $this->consume('paren_close');

        $this->skipWhitespace();

        $this->skipWhitespace();

        $this->consume('brace_open');

        $content = $this->parseBody();

        return new FunctionNode($name, $args, $content);
    }

    /**
     * @throws SyntaxException
     */
    protected function parseReturn(): AstNode
    {
        $this->consume('at_rule');

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

                            $defaultValue = $this->parseBinaryExpression(0);

                            $args[$argName] = $defaultValue;
                        } else {
                            $args[$argName] = new NullNode($varToken->line);
                        }
                    } else {
                        $this->consume('identifier');
                    }

                    if (! $this->peek('paren_close')) {
                        $this->skipWhitespace();

                        $commaToken = $this->stream->current();
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

    /**
     * @throws SyntaxException
     */
    protected function parseBinaryExpression(int $minPrecedence): AstNode
    {
        $this->skipWhitespace();

        $token = $this->stream->current();
        if ($token && (isset(self::UNARY_OPERATORS[$token->value]) || $token->type === 'unary_operator')) {
            $operator = $token->value;
            $line = $token->line;

            $this->advanceToken();

            $operand = $this->parseBinaryExpression(5);

            return new UnaryNode($operator, $operand, $line);
        }

        $left = $this->parsePrimaryExpression();

        $this->skipWhitespace();

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
                $this->advanceToken();

                $right = $this->parsePrimaryExpression();

                $this->skipWhitespace();

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
                $this->advanceToken();
            }

            $this->skipWhitespace();

            $right = $this->parseBinaryExpression($precedence + 1);
            $left  = new OperationNode($left, $operator, $right, $token->line);
        }

        return $left;
    }

    protected function checkIfSelector(): bool
    {
        if ($this->peek('function')) {
            $parenLevel = 1;

            $this->advanceToken();

            if ($this->peek('paren_open')) {
                $this->consume('paren_open');

                while ($this->stream->current() && $parenLevel > 0) {
                    if ($this->peek('paren_open')) {
                        $parenLevel++;
                    } elseif ($this->peek('paren_close')) {
                        $parenLevel--;
                    }

                    $this->advanceToken();
                }
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

    /**
     * @throws SyntaxException
     */
    protected function parseBody(): array
    {
        $body = $this->parseBlock();

        return $body['items'] ?? array_merge($body['declarations'], $body['nested']);
    }

    private function parseImport(): AstNode
    {
        $token = $this->consume('at_rule');

        $this->skipWhitespace();

        $rawValue = $this->captureImportValue();
        $normalizedValue = $this->normalizeImportPath($rawValue);

        $this->consume('semicolon');

        return new AtRuleNode('@import', $normalizedValue, null, $token->line);
    }

    private function captureImportValue(): string
    {
        $value = '';
        $previousToken = null;

        while ($this->stream->current() && ! $this->peek('semicolon')) {
            $currentToken = $this->stream->current();

            if ($previousToken && $this->shouldAddSpace($previousToken, $currentToken)) {
                $value .= ' ';
            }

            $value .= $currentToken->value;
            $previousToken = $currentToken;

            $this->advanceToken();
        }

        return trim($value);
    }

    private function shouldAddSpace(Token $previous, Token $current): bool
    {
        if (in_array($current->type, ['colon', 'comma', 'semicolon', 'paren_close'], true)) {
            return false;
        }

        if ($current->type === 'paren_open') {
            return in_array($previous->type, ['identifier', 'logical_operator'], true);
        }

        if ($previous->type === 'paren_open') {
            return false;
        }

        if ($previous->type === 'colon') {
            return $current->type === 'identifier';
        }

        return true;
    }

    private function normalizeImportPath(string $value): string
    {
        if (str_starts_with($value, 'url(')) {
            $content = preg_replace('/^url\((.*)\)$/s', '$1', $value);
            $content = trim($content);

            if (str_starts_with($content, "'") && str_ends_with($content, "'")) {
                $content = StringFormatter::forceQuoteString(trim($content, "'"));
            } elseif (! str_starts_with($content, '"') && ! str_ends_with($content, '"')) {
                $content = StringFormatter::forceQuoteString($content);
            }

            return 'url(' . $content . ')';
        }

        if (! str_contains($value, ' ')) {
            return trim($value, '"\'');
        }

        return $value;
    }


    /**
     * @throws SyntaxException
     */
    private function parseSelector(): AstNode
    {
        $value = '';
        $line  = $this->stream->current()->line;

        while (
            ($token = $this->stream->current())
            && ! $this->stream->matchesAny('brace_open', 'semicolon')
        ) {
            if ($token->type === 'comment') {
                $this->advanceToken();

                continue;
            }

            if (isset(self::SELECTOR_TOKENS[$token->type])) {
                // Add space between certain token combinations in selectors
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

    private function needsSpaceBeforeToken(string $currentValue, Token $nextToken): bool
    {
        // Get the last character of current value
        $lastChar = substr($currentValue, -1);

        // Don't add space if current value ends with selector operators
        if (in_array($lastChar, ['.', '#', '>', '+', '~', '&', ':'], true)) {
            return false;
        }

        // Don't add space before selector operators
        if (in_array($nextToken->value, ['.', '#', '>', '+', '~', '&', ':'], true)) {
            return false;
        }

        // Add space between identifiers (e.g., 'pre span')
        if ($nextToken->type === 'identifier') {
            return true;
        }

        return false;
    }

    /**
     * @throws SyntaxException
     */
    private function parseCssPropertyValue(): AstNode
    {
        $propertyToken = $this->consume('identifier');

        $this->consume('colon');

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

        $this->advanceToken();

        return match ($token->type) {
            'number' => $this->parseNumber($token),

            'identifier' => (function () use ($token) {
                if ($this->peek('colon')) {
                    $this->setTokenIndex($this->getTokenIndex() - 1);

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

            'function' => $this->parseFunctionCall($token),

            'paren_open' => (function () use ($token) {
                $this->skipWhitespace();

                if ($this->peek('paren_close')) {
                    $this->consume('paren_close');

                    return new ListNode([], 'space', line: $token->line);
                }

                // Check if this looks like a map: (key: value, key2: value2)
                $savedPosition = $this->getTokenIndex();
                $mapResult     = $this->tryParseMapWithConsume();

                if ($mapResult !== null) {
                    return $mapResult;
                }

                // Restore position and parse as regular expression
                $this->setTokenIndex($savedPosition);

                $node = $this->parseExpression();

                $this->consume('paren_close');

                return $node;
            })(),

            'interpolation_open' => (function () use ($token) {
                $expression = $this->parseExpression();

                $this->consume('brace_close');

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

                return new ListNode($values, bracketed: true, line: $token->line);
            })(),

            'operator', 'asterisk', 'colon', 'semicolon' => new OperatorNode($token->value, $token->line),

            'important_modifier' => new IdentifierNode('!important', $token->line),

            'spread_operator' => (function () use ($token): void {
                throw new SyntaxException(
                    'Spread operator (...) can only be used in function calls',
                    $token->line,
                    $token->column
                );
            })(),
        };
    }

    /**
     * @throws SyntaxException
     */
    private function parseFunctionCall(object $token): FunctionNode|ColorNode
    {
        $funcName = $token->value;

        $this->consume('paren_open');

        $args = $this->parseArgumentList(includeSpreads: true);

        $this->consume('paren_close');

        if (isset(self::COLOR_FUNCTIONS[$funcName])) {
            return $this->createColorNode($funcName, $args, $token->line);
        }

        return new FunctionNode($funcName, $args, line: $token->line);
    }

    /**
     * @throws SyntaxException
     */
    private function parseArgumentList(bool $includeSpreads = false): array
    {
        $args = [];

        while (! $this->peek('paren_close')) {
            $this->skipWhitespace();

            if ($this->peek('paren_close')) {
                break;
            }

            $namedArg = $this->tryParseNamedArgument();

            if ($namedArg !== null) {
                [$argName, $argValue] = $namedArg;

                $args[$argName] = $argValue;

                $this->consumeCommaIfPresent();

                continue;
            }

            $arg = $this->parseBinaryExpression(0);

            if ($includeSpreads) {
                $arg = $this->maybeExpandListArgument($arg);
            }

            $args[] = $this->maybeWrapWithSpread($arg);

            $this->consumeCommaIfPresent();
        }

        return $args;
    }

    /**
     * @throws SyntaxException
     */
    private function tryParseNamedArgument(): ?array
    {
        if (! $this->peek('variable')) {
            return null;
        }

        $varToken = $this->consume('variable');
        $argName  = $varToken->value;

        $this->skipWhitespace();

        if (! $this->peek('colon')) {
            $this->setTokenIndex($this->getTokenIndex() - 1);

            return null;
        }

        $this->consume('colon');
        $this->skipWhitespace();

        $argValue = $this->parseBinaryExpression(0);

        return [$argName, $argValue];
    }

    private function consumeCommaIfPresent(): void
    {
        if ($this->peek('paren_close')) {
            return;
        }

        $this->skipWhitespace();

        $commaToken = $this->stream->current();
        if ($commaToken && $commaToken->type === 'operator' && $commaToken->value === ',') {
            $this->advanceToken();
        }
    }

    /**
     * @throws SyntaxException
     */
    private function maybeExpandListArgument(AstNode $arg): AstNode
    {
        $this->skipWhitespace();

        $next = $this->stream->current();

        if ($next
            && ! $this->peek('paren_close')
            && ! ($next->type === 'operator' && $next->value === ',')
            && ! ($next->type === 'spread_operator')
            && ! isset(self::BLOCK_END_TYPES[$next->type])
        ) {
            $values = [$arg];

            while (
                $this->stream->current()
                && ! $this->peek('paren_close')
                && ! ($this->stream->current()->type === 'operator' && $this->stream->current()->value === ',')
                && ! ($this->stream->current()->type === 'spread_operator')
                && ! isset(self::BLOCK_END_TYPES[$this->stream->current()->type])
            ) {
                $values[] = $this->parseBinaryExpression(0);

                $this->skipWhitespace();
            }

            return new ListNode($values, 'space', line: $arg->line ?? 0);
        }

        return $arg;
    }

    /**
     * @throws SyntaxException
     */
    private function maybeWrapWithSpread(AstNode $arg): array|AstNode
    {
        $this->skipWhitespace();

        if ($this->peek('spread_operator')) {
            $this->consume('spread_operator');
            $this->skipWhitespace();

            if (! $this->peek('paren_close')) {
                throw new SyntaxException(
                    'Spread operator (...) must be the last argument',
                    $this->stream->current()->line,
                    $this->stream->current()->column
                );
            }

            return ['type' => 'spread', 'value' => $arg];
        }

        return $arg;
    }

    private function createColorNode(string $funcName, array $args, int $line): ColorNode
    {
        $alpha = null;

        $components = [];
        foreach ($args as $arg) {
            if ($arg instanceof ListNode) {
                foreach ($arg->values as $value) {
                    $components[] = $this->extractColorComponent($value);
                }
            } else {
                $components[] = $this->extractColorComponent($arg);
            }
        }

        if (count($components) > 0) {
            $lastComponent = $components[count($components) - 1];
            if (is_string($lastComponent) && str_contains($lastComponent, '/')) {
                [$colorPart, $alphaPart] = explode('/', $lastComponent, 2);
                $components[count($components) - 1] = trim($colorPart);
                $alpha = (float) trim($alphaPart);
            }
        }

        return new ColorNode($funcName, $components, $alpha, $line);
    }

    private function extractColorComponent(AstNode $node): string|int|float
    {
        return match ($node->type) {
            NodeType::NUMBER    => $node->value ?? '',
            NodeType::OPERATION => $this->extractOperationComponent($node),
            default             => (string) $node,
        };
    }

    private function extractOperationComponent(OperationNode|AstNode $node): string
    {
        if ($node->operator === '/') {
            $left  = $this->extractColorComponent($node->left);
            $right = $this->extractColorComponent($node->right);

            return "$left/$right";
        }

        return (string) $node;
    }

    private function parseNameAndInit(): array
    {
        $this->consume('at_rule');
        $this->skipWhitespace();

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
        $token    = $this->stream->current();
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
        $this->consume('interpolation_open');

        $expression = $this->parseExpression();

        $this->consume('brace_close');

        return $this->formatExpressionForSelector($expression);
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

    private function parseNumber(Token $token): NumberNode
    {
        $valueStr = $token->value;

        if (preg_match('/^(-?\d*\.?\d+)(.*)$/', $valueStr, $matches)) {
            $value = (float) $matches[1];
            $unit  = trim($matches[2]) ?: null;
        } else {
            $value = 0.0;
            $unit  = null;
        }

        return new NumberNode($value, $unit, $token->line);
    }

    /**
     * @throws SyntaxException
     */
    private function parseMapValue(): StringNode|IdentifierNode|AstNode|null
    {
        $token = $this->stream->current();

        if (! $token) {
            return null;
        }

        $startPosition = $this->getTokenIndex();

        switch ($token->type) {
            case 'number':
                $this->advanceToken();

                return $this->parseNumber($token);

            case 'string':
                $this->advanceToken();

                return new StringNode(trim($token->value, "'\""), $token->line);

            case 'identifier':
                $this->advanceToken();

                return new IdentifierNode($token->value, $token->line);

            case 'hex_color':
                $this->advanceToken();

                return new HexColorNode($token->value, $token->line);

            case 'paren_open':
                return $this->parseExpression();

            default:
                $value = $this->parseExpression();

                if ($value instanceof ListNode) {
                    $this->setTokenIndex($startPosition);

                    return null;
                }

                return $value;
        }
    }

    /**
     * @throws SyntaxException
     */
    private function tryParseMapWithConsume(): ?MapNode
    {
        $pairs = [];

        $position = $this->getTokenIndex();

        while (! $this->peek('paren_close')) {
            $keyToken = $this->stream->current();
            if (! $keyToken || ! in_array($keyToken->type, ['identifier', 'string'], true)) {
                $this->setTokenIndex($position);

                return null;
            }

            $key = $keyToken->value;

            $this->advanceToken();

            if (! $this->peek('colon')) {
                $this->setTokenIndex($position);

                return null;
            }

            $this->consume('colon');

            $valueStartPos = $this->getTokenIndex();

            $value = $this->parseMapValue();

            if ($value === null) {
                $this->setTokenIndex($position);

                return null;
            }

            if ($value instanceof ListNode || $value instanceof CssPropertyNode) {
                $this->setTokenIndex($valueStartPos);

                $parenLevel = 0;

                while (
                    $this->stream->current()
                    && (! $this->peek('operator') || $this->stream->current()?->value !== ',' || $parenLevel > 0)
                ) {
                    if ($this->peek('paren_open')) {
                        $parenLevel++;
                    } elseif ($this->peek('paren_close')) {
                        $parenLevel--;
                    }

                    if ($parenLevel === 0 && $this->peek('operator') && $this->stream->current()?->value === ',') {
                        break;
                    }

                    $this->advanceToken();
                }

                $this->setTokenIndex($valueStartPos);

                $value = $this->parseExpression();
            }

            $pairs[] = [$key, $value];

            if ($this->peek('operator') && $this->stream->current()?->value === ',') {
                $this->consume('operator');
                $this->skipWhitespace();
            } elseif ($this->peek('paren_close')) {
                $this->consume('paren_close');

                return new MapNode($pairs, $position > 0 ? $this->getTokens()[$position - 1]->line : 0);
            } else {
                $this->setTokenIndex($position);

                return null;
            }
        }

        $this->consume('paren_close');

        return empty($pairs)
            ? null
            : new MapNode($pairs, $position > 0 ? $this->getTokens()[$position - 1]->line : 0);
    }
}
