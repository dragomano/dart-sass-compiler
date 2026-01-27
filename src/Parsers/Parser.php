<?php

declare(strict_types=1);

namespace DartSass\Parsers;

use DartSass\Exceptions\SyntaxException;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\CommentNode;
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

class Parser extends AbstractParser
{
    private const PARSER_CLASSES = [
        'expression' => ExpressionParser::class,
        'block'      => BlockParser::class,
        'selector'   => SelectorParser::class,
        'function'   => FunctionParser::class,
        'import'     => ImportParser::class,
    ];

    private array $parsers = [];

    protected function getParser(string $type): ParserInterface
    {
        if (! isset($this->parsers[$type])) {
            $class = self::PARSER_CLASSES[$type];

            $this->parsers[$type] = new $class($this->getStream());
        }

        return $this->parsers[$type];
    }

    /**
     * @throws SyntaxException
     */
    public function parse(): array
    {
        $rules = [];

        while (! $this->isEnd()) {
            $token = $this->currentToken();

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

    /**
     * @throws SyntaxException
     */
    public function parseRule(): AstNode
    {
        /* @var BlockParser $parser */
        $parser = $this->getParser('block');

        return $parser->parseRule();
    }

    /**
     * @throws SyntaxException
     */
    public function parseDeclaration(): array
    {
        /* @var BlockParser $parser */
        $parser = $this->getParser('block');

        return $parser->parseDeclaration();
    }

    public function parseExpression(): AstNode
    {
        return $this->getParser('expression')->parse();
    }

    public function parseBlock(): array
    {
        return $this->getParser('block')->parse();
    }

    /**
     * @throws SyntaxException
     */
    public function parseVariable(): AstNode
    {
        /* @var BlockParser $parser */
        $parser = $this->getParser('block');

        return $parser->parseVariable();
    }

    private function parseFunction(): AstNode
    {
        return $this->getParser('function')->parse();
    }

    /**
     * @throws SyntaxException
     */
    private function parseMixin(): AstNode
    {
        /* @var FunctionParser $parser */
        $parser = $this->getParser('function');

        return $parser->parseMixin();
    }

    /**
     * @throws SyntaxException
     */
    private function parseInclude(): AstNode
    {
        /* @var BlockParser $parser */
        $parser = $this->getParser('block');

        return $parser->parseInclude();
    }

    private function parseImport(): AstNode
    {
        return $this->getParser('import')->parse();
    }

    private function parseAtRule(): AstNode
    {
        $parser = $this->createAtRuleParser();

        return $parser->parse();
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
}
