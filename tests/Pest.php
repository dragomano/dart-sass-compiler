<?php

declare(strict_types=1);

use PHPUnit\Framework\Assert;

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

expect()->extend('toEqualCss', function (string $expected) {
    $normalize = static function (string $s): string {
        return str_replace(["\r\n", "\r"], "\n", trim($s));
    };

    $actualNorm   = $normalize($this->value);
    $expectedNorm = $normalize($expected);

    return expect($actualNorm)->toBe($expectedNorm);
});

expect()->extend('toBeCloseTo', function (float $expected, float $delta = 0.01) {
    Assert::assertEqualsWithDelta($expected, $this->value, $delta);

    return $this;
});
