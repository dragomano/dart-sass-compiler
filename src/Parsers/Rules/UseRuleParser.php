<?php

declare(strict_types=1);

namespace DartSass\Parsers\Rules;

use DartSass\Exceptions\SyntaxException;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\UseNode;

use function array_map;
use function basename;
use function implode;
use function ltrim;
use function preg_replace;
use function sprintf;
use function trim;

class UseRuleParser extends AtRuleParser
{
    /**
     * @throws SyntaxException
     */
    public function parse(): AstNode
    {
        $token = $this->consume('at_rule');

        if ($token->value !== '@use') {
            throw new SyntaxException(
                sprintf('Expected @use rule, got %s', $token->value),
                $token->line,
                $token->column
            );
        }

        while ($this->peek('whitespace')) {
            $this->incrementTokenIndex();
        }

        $namespace = null;
        if (! $this->peek('string')) {
            $pathTokens = [];
            while ($this->currentToken() && ! $this->peek('semicolon') && ! $this->peek('brace_open') && ! $this->peek('as')) {
                $pathTokens[] = $this->consume($this->currentToken()->type);
            }

            $path = trim(implode('', array_map(fn ($t): string => $t->value, $pathTokens)));

            if ($this->currentToken() && $this->peek('as')) {
                $this->consume('as');
                while ($this->currentToken() && $this->peek('whitespace')) {
                    $this->incrementTokenIndex();
                }

                if ($this->currentToken() && $this->peek('asterisk')) {
                    $namespace = $this->consume('asterisk')->value;
                } elseif ($this->currentToken() && $this->peek('identifier')) {
                    $namespace = $this->consume('identifier')->value;
                }
            }
        } else {
            $pathToken = $this->consume('string');
            $path = trim(trim($pathToken->value, '\'"'));

            while ($this->currentToken() && $this->peek('whitespace')) {
                $this->incrementTokenIndex();
            }

            if ($this->currentToken() && $this->peek('identifier')) {
                $asOption = $this->consume('identifier')->value;

                if ($asOption === 'as') {
                    while ($this->currentToken() && $this->peek('whitespace')) {
                        $this->incrementTokenIndex();
                    }

                    if ($this->currentToken() && $this->peek('asterisk')) {
                        $namespace = $this->consume('asterisk')->value;
                    } else {
                        $namespace = $this->consume('identifier')->value;
                    }
                }
            }
        }

        $this->consume('semicolon');

        // If no namespace specified, use default (filename without extension and leading underscore)
        if ($namespace === null) {
            $namespace = $this->getDefaultNamespace($path);
        }

        return new UseNode($path, $namespace, $token->line);
    }

    private function getDefaultNamespace(string $path): string
    {
        $filename = basename($path);
        $filename = preg_replace('/\.[^.]+$/', '', $filename);

        return ltrim((string) $filename, '_');
    }
}
