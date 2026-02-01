<?php

declare(strict_types=1);

namespace DartSass\Parsers\Rules;

use Closure;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Nodes\AtRootNode;
use DartSass\Parsers\Nodes\MediaNode;
use DartSass\Parsers\Nodes\RuleNode;
use DartSass\Parsers\Tokens\TokenStreamInterface;

use function preg_match;
use function trim;

class MediaRuleParser extends AtRuleParser
{
    use BlockParsingHelper;

    public function __construct(
        TokenStreamInterface $stream,
        protected Closure    $parseAtRule,
        protected Closure    $parseInclude,
        protected Closure    $parseVariable,
        protected Closure    $parseRule,
        protected Closure    $parseDeclaration
    ) {
        parent::__construct($stream);
    }

    public function parse(): AstNode
    {
        $token = $this->consume('at_rule');
        $query = $this->parseMediaQuery();

        $this->consume('brace_open');

        $body = $this->parseBlock();

        $this->consume('brace_close');

        $atRootRule = $this->extractAtRootWithoutMedia($body);

        if ($atRootRule !== null) {
            return $atRootRule;
        }

        return $this->createNode($query, $body, $token->line);
    }

    protected function createNode(string $query, array $body, int $line): AstNode
    {
        return new MediaNode($query, $body, $line);
    }

    private function parseMediaQuery(): string
    {
        $query = '';

        while ($this->currentToken() && ! $this->peek('brace_open') && ! $this->peek('newline')) {
            $currentToken = $this->currentToken();

            if ($query !== '' && $this->shouldAddSpace($currentToken, $query)) {
                $query .= ' ';
            }

            $query .= $currentToken->value;

            $this->incrementTokenIndex();
        }

        return trim($query);
    }

    private function extractAtRootWithoutMedia(array $body): ?RuleNode
    {
        $hasAtRootWithoutMedia = false;

        $filteredNested = [];
        foreach ($body['nested'] as $nested) {
            if ($nested instanceof RuleNode) {
                foreach ($nested->nested as $nestedItem) {
                    if ($nestedItem instanceof AtRootNode && $nestedItem->without === 'media') {
                        $hasAtRootWithoutMedia = true;

                        $newRule = new RuleNode(
                            $nested->selector,
                            $nestedItem->body['declarations'],
                            $nestedItem->body['nested'],
                            $nested->line,
                            $nested->column
                        );

                        $filteredNested[] = $newRule;

                        break;
                    }
                }

                if (! $hasAtRootWithoutMedia) {
                    $filteredNested[] = $nested;
                }
            } else {
                $filteredNested[] = $nested;
            }
        }

        if ($hasAtRootWithoutMedia) {
            return $filteredNested[0];
        }

        return null;
    }

    private function shouldAddSpace($currentToken, string $query): bool
    {
        if ($currentToken->type === 'logical_operator') {
            return true;
        }

        if ($currentToken->type === 'identifier' && preg_match('/\d$/', $query) === 1) {
            return false;
        }

        if ($currentToken->type === 'identifier') {
            if (preg_match('/[a-zA-Z0-9_-]+$/', $query) === 1) {
                return true;
            }
        }

        if ($currentToken->type === 'paren_open') {
            return preg_match('/\b(and|or)\b$/', $query) === 1;
        }

        if ($currentToken->type === 'colon') {
            return false;
        }

        if ($currentToken->type === 'number') {
            if (preg_match('/:$/', $query) === 1) {
                return true;
            }
        }

        return false;
    }
}
