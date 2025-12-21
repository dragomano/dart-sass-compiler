<?php

declare(strict_types=1);

namespace DartSass\Normalizers;

use DartSass\Parsers\Syntax;

readonly class NoOpNormalizer implements SourceNormalizer
{
    public function supports(Syntax $syntax): bool
    {
        return $syntax === Syntax::SCSS;
    }

    public function normalize(string $source): string
    {
        return $source;
    }
}
