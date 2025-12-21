<?php

declare(strict_types=1);

namespace DartSass\Parsers;

final class ParserFactory
{
    public function __construct(private ?LexerInterface $lexer = null)
    {
        $this->lexer ??= new Lexer();
    }

    public function create(string $content, Syntax $syntax): ParserInterface
    {
        $stream = $this->lexer->tokenize($content, $syntax);

        return match ($syntax) {
            Syntax::SASS => new SassParser($stream),
            Syntax::SCSS => new ScssParser($stream),
        };
    }

    public function createFromPath(string $content, string $path): ParserInterface
    {
        return $this->create($content, Syntax::fromPath($path));
    }
}
