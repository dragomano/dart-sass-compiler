<?php

declare(strict_types=1);

namespace DartSass\Parsers;

use DartSass\Normalizers\NoOpNormalizer;
use DartSass\Normalizers\SassNormalizer;
use DartSass\Parsers\Tokens\Lexer;
use DartSass\Parsers\Tokens\LexerInterface;

final class ParserFactory
{
    private const NORMALIZERS = [
        NoOpNormalizer::class,
        SassNormalizer::class,
    ];

    private array $normalizerInstances = [];

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

        return new Parser($stream);
    }

    public function createFromPath(string $content, string $path): ParserInterface
    {
        return $this->create($content, Syntax::fromPath($path, $content));
    }

    private function getNormalizers(): array
    {
        if (empty($this->normalizerInstances)) {
            foreach (self::NORMALIZERS as $normalizerClass) {
                $this->normalizerInstances[] = new $normalizerClass();
            }
        }

        return $this->normalizerInstances;
    }
}
