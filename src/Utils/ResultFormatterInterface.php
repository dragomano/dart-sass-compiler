<?php

declare(strict_types=1);

namespace DartSass\Utils;

interface ResultFormatterInterface
{
    public function format(mixed $result): string;
}
