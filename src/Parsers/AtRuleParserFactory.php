<?php

declare(strict_types=1);

namespace DartSass\Parsers;

use DartSass\Parsers\Nodes\AstNode;
use DartSass\Parsers\Rules\AtRootRuleParser;
use DartSass\Parsers\Rules\AtRuleParser;
use DartSass\Parsers\Rules\ContainerRuleParser;
use DartSass\Parsers\Rules\DebugRuleParser;
use DartSass\Parsers\Rules\EachRuleParser;
use DartSass\Parsers\Rules\ErrorRuleParser;
use DartSass\Parsers\Rules\ForRuleParser;
use DartSass\Parsers\Rules\ForwardRuleParser;
use DartSass\Parsers\Rules\GenericAtRuleParser;
use DartSass\Parsers\Rules\IfRuleParser;
use DartSass\Parsers\Rules\KeyframesRuleParser;
use DartSass\Parsers\Rules\MediaRuleParser;
use DartSass\Parsers\Rules\UseRuleParser;
use DartSass\Parsers\Rules\WarnRuleParser;
use DartSass\Parsers\Rules\WhileRuleParser;

trait AtRuleParserFactory
{
    protected function parseAtRule(): AstNode
    {
        $parser = $this->createAtRuleParser();

        return $parser->parse();
    }

    protected function createAtRuleParser(): AtRuleParser
    {
        $stream = $this->getStream();

        return match ($this->currentToken()->value) {
            '@debug'     => new DebugRuleParser($stream, $this->parseExpression(...)),
            '@warn'      => new WarnRuleParser($stream, $this->parseExpression(...)),
            '@error'     => new ErrorRuleParser($stream, $this->parseExpression(...)),
            '@use'       => new UseRuleParser($stream),
            '@forward'   => new ForwardRuleParser($stream),
            '@for'       => new ForRuleParser($stream, $this->parseExpression(...), $this->parseBlock(...)),
            '@while'     => new WhileRuleParser($stream, $this->parseExpression(...), $this->parseBlock(...)),
            '@if'        => new IfRuleParser($stream, $this->parseExpression(...), $this->parseBlock(...)),
            '@each'      => new EachRuleParser($stream, $this->parseExpression(...), $this->parseBlock(...)),
            '@keyframes' => new KeyframesRuleParser($stream, $this->parseExpression(...)),
            '@at-root'   => new AtRootRuleParser(
                $stream,
                $this->parseAtRule(...),
                $this->parseInclude(...),
                $this->parseVariable(...),
                $this->parseRule(...),
                $this->parseDeclaration(...)
            ),
            '@media'     => new MediaRuleParser(
                $stream,
                $this->parseAtRule(...),
                $this->parseInclude(...),
                $this->parseVariable(...),
                $this->parseRule(...),
                $this->parseDeclaration(...)
            ),
            '@container' => new ContainerRuleParser(
                $stream,
                $this->parseAtRule(...),
                $this->parseInclude(...),
                $this->parseVariable(...),
                $this->parseRule(...),
                $this->parseDeclaration(...)
            ),
            default      => new GenericAtRuleParser(
                $stream,
                $this->parseAtRule(...),
                $this->parseVariable(...),
                $this->parseRule(...),
                $this->parseDeclaration(...)
            ),
        };
    }
}
