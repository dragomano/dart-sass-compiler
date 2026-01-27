<?php

declare(strict_types=1);

namespace DartSass\Parsers;

use DartSass\Exceptions\SyntaxException;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\CommentNode;

class Parser extends AbstractParser
{
    use AtRuleParserFactory;

    private ?BlockParser $blockParser = null;

    private ?ExpressionParser $expressionParser = null;

    private ?SelectorParser $selectorParser = null;

    private ?FunctionParser $functionParser = null;

    private ?ImportParser $importParser = null;

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
        return $this->blockParser()->parseRule();
    }

    public function parseDeclaration(): array
    {
        return $this->blockParser()->parseDeclaration();
    }

    /**
     * @throws SyntaxException
     */
    public function parseExpression(): AstNode
    {
        return $this->expressionParser()->parse();
    }

    /**
     * @throws SyntaxException
     */
    private function parseBlock(): array
    {
        return $this->blockParser()->parse();
    }

    private function parseVariable(): AstNode
    {
        return $this->blockParser()->parseVariable();
    }

    /**
     * @throws SyntaxException
     */
    private function parseFunction(): AstNode
    {
        return $this->functionParser()->parse();
    }

    /**
     * @throws SyntaxException
     */
    private function parseBinaryExpression(): AstNode
    {
        return $this->expressionParser()->parseBinaryExpression(0);
    }

    /**
     * @throws SyntaxException
     */
    private function parsePrimaryExpression(): AstNode
    {
        return $this->expressionParser()->parsePrimaryExpression();
    }

    private function parseMixin(): AstNode
    {
        return $this->functionParser()->parseMixin();
    }

    /**
     * @throws SyntaxException
     */
    private function parseInclude(): AstNode
    {
        return $this->blockParser()->parseInclude();
    }

    private function parseImport(): AstNode
    {
        return $this->importParser()->parse();
    }

    /**
     * @throws SyntaxException
     */
    private function parseSelector(): AstNode
    {
        return $this->selectorParser()->parse();
    }

    /**
     * @throws SyntaxException
     */
    private function parseArgumentExpression(): array
    {
        return $this->expressionParser()->parseArgumentList();
    }

    private function blockParser(): BlockParser
    {
        return $this->blockParser ??= new BlockParser(
            $this->getStream(),
            $this->parseExpression(...),
            $this->parsePrimaryExpression(...),
            $this->parseArgumentExpression(...),
            $this->parseSelector(...)
        );
    }

    private function expressionParser(): ExpressionParser
    {
        return $this->expressionParser ??= new ExpressionParser($this->getStream());
    }

    private function functionParser(): FunctionParser
    {
        return $this->functionParser ??= new FunctionParser(
            $this->getStream(),
            $this->parseBlock(...),
            $this->parseBinaryExpression(...)
        );
    }

    private function importParser(): ImportParser
    {
        return $this->importParser ??= new ImportParser($this->getStream());
    }

    private function selectorParser(): SelectorParser
    {
        return $this->selectorParser ??= new SelectorParser($this->getStream(), $this->parseExpression(...));
    }
}
