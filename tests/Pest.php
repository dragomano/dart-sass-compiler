<?php declare(strict_types=1);

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
