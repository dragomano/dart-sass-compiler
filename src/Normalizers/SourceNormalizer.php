<?php

declare(strict_types=1);

namespace DartSass\Normalizers;

use DartSass\Parsers\Syntax;

interface SourceNormalizer
{
    public function supports(Syntax $syntax): bool;

    public function normalize(string $source): string;
}
