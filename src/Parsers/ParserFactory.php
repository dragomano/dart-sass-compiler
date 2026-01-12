<?php

declare(strict_types=1);

namespace DartSass\Parsers;

use DartSass\Normalizers\NoOpNormalizer;
use DartSass\Normalizers\SassToScssNormalizer;

final class ParserFactory
{
    public function __construct(private ?LexerInterface $lexer = null)
    {
        $this->lexer ??= new Lexer();
    }

    public function create(string $content, Syntax $syntax): ParserInterface
    {
        foreach ($this->getNormalizers() as $normalizer) {
            if ($normalizer->supports($syntax)) {
                $content = $normalizer->normalize($content);

                break;
            }
        }

        $stream = $this->lexer->tokenize($content);

        return new ScssParser($stream);
    }

    public function createFromPath(string $content, string $path): ParserInterface
    {
        return $this->create($content, Syntax::fromPath($path, $content));
    }

    private function getNormalizers(): array
    {
        return [
            new NoOpNormalizer(),
            new SassToScssNormalizer(),
        ];
    }
}
