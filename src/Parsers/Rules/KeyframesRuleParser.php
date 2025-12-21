<?php

declare(strict_types=1);

namespace DartSass\Parsers\Rules;

use DartSass\Exceptions\SyntaxException;
use DartSass\Parsers\Nodes\AstNode;

use DartSass\Parsers\Nodes\KeyframesNode;

use function sprintf;
use function trim;

class KeyframesRuleParser extends AtRuleParser
{
    /**
     * @throws SyntaxException
     */
    public function parse(): AstNode
    {
        $token = $this->consume('at_rule');
        $name = $token->value;

        $animationName = '';
        while ($this->currentToken() && ! $this->peek('brace_open')) {
            $currentToken = $this->currentToken();
            $animationName .= $currentToken->value;

            $this->incrementTokenIndex();
        }

        $animationName = trim($animationName);

        if (! $this->peek('brace_open')) {
            throw new SyntaxException(
                sprintf('Expected "{" after %s', $name),
                $this->currentToken() ? $this->currentToken()->line : $token->line,
                $this->currentToken() ? $this->currentToken()->column : $token->column
            );
        }

        $this->consume('brace_open');

        $keyframes = $this->parseKeyframes();

        return new KeyframesNode($animationName, $keyframes, $token->line);
    }

    private function parseKeyframes(): array
    {
        $keyframes = [];

        while ($this->currentToken() && ! $this->peek('brace_close')) {
            while ($this->peek('whitespace')) {
                $this->incrementTokenIndex();
            }

            if ($this->peek('brace_close')) {
                break;
            }

            $selectors = $this->parseKeyframeSelectors();

            $this->consume('brace_open');

            $declarations = $this->parseKeyframeDeclarations();

            $this->consume('brace_close');

            $keyframes[] = [
                'selectors'    => $selectors,
                'declarations' => $declarations,
            ];
        }

        $this->consume('brace_close');

        return $keyframes;
    }

    private function parseKeyframeSelectors(): array
    {
        $selectors = [];

        while ($this->currentToken() && ! $this->peek('brace_open')) {
            while ($this->peek('whitespace')) {
                $this->incrementTokenIndex();
            }

            if ($this->peek('brace_open')) {
                break;
            }

            $selector = '';
            while ($this->currentToken() && ! $this->peek('comma') && ! $this->peek('brace_open')) {
                $currentToken = $this->currentToken();
                $selector .= $currentToken->value;
                $this->incrementTokenIndex();
            }

            $selectors[] = trim($selector);

            if ($this->peek('comma')) {
                $this->consume('comma');
            }
        }

        return $selectors;
    }

    private function parseKeyframeDeclarations(): array
    {
        $declarations = [];

        while ($this->currentToken() && ! $this->peek('brace_close')) {
            while ($this->peek('whitespace')) {
                $this->incrementTokenIndex();
            }

            if ($this->peek('brace_close')) {
                break;
            }

            $propertyToken = $this->consume('identifier');
            $property = $propertyToken->value;

            $this->consume('colon');

            while ($this->peek('whitespace')) {
                $this->incrementTokenIndex();
            }

            $value = $this->parser->parseExpression();

            $this->consume('semicolon');

            $declarations[] = [$property => $value];
        }

        return $declarations;
    }
}
