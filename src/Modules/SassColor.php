<?php

declare(strict_types=1);

namespace DartSass\Modules;

use Stringable;

readonly class SassColor implements Stringable
{
    private const DEFAULT_ALPHA = 1.0;

    private const DEFAULT_CHANNEL = 0.0;

    public function __construct(public array $data, public ?string $format = null) {}

    public function __toString(): string
    {
        $data = $this->data;

        if (! isset($data['format'])) {
            $data['format'] = ColorFormat::RGB->value;
        }

        $format = ColorFormat::tryFrom($data['format']);

        return ColorSerializer::format($format ?? ColorFormat::RGB, $this);
    }

    public function getRed(): float
    {
        return $this->data['r'] ?? self::DEFAULT_CHANNEL;
    }

    public function getGreen(): float
    {
        return $this->data['g'] ?? self::DEFAULT_CHANNEL;
    }

    public function getBlue(): float
    {
        return $this->data['b'] ?? self::DEFAULT_CHANNEL;
    }

    public function getAlpha(): float
    {
        return $this->data['a'] ?? self::DEFAULT_ALPHA;
    }

    public function getHue(): float
    {
        return $this->data['h'] ?? self::DEFAULT_CHANNEL;
    }

    public function getSaturation(): float
    {
        return $this->data['s'] ?? self::DEFAULT_CHANNEL;
    }

    public function getLightness(): float
    {
        return $this->data['l'] ?? self::DEFAULT_CHANNEL;
    }

    public function getWhiteness(): float
    {
        return $this->data['w'] ?? self::DEFAULT_CHANNEL;
    }

    public function getBlackness(): float
    {
        return $this->data['bl'] ?? self::DEFAULT_CHANNEL;
    }

    public function getChroma(): float
    {
        return $this->data['c'] ?? self::DEFAULT_CHANNEL;
    }

    public function getLabL(): float
    {
        return $this->data['lab_l'] ?? self::DEFAULT_CHANNEL;
    }

    public function getLabA(): float
    {
        return $this->data['lab_a'] ?? self::DEFAULT_CHANNEL;
    }

    public function getLabB(): float
    {
        return $this->data['lab_b'] ?? self::DEFAULT_CHANNEL;
    }

    public function getX(): float
    {
        return $this->data['x'] ?? self::DEFAULT_CHANNEL;
    }

    public function getY(): float
    {
        return $this->data['y'] ?? self::DEFAULT_CHANNEL;
    }

    public function getZ(): float
    {
        return $this->data['z'] ?? self::DEFAULT_CHANNEL;
    }

    public function getFormat(): ?string
    {
        return $this->format ?? $this->data['format'] ?? null;
    }

    public function getSupportedConversions(): array
    {
        return [
            ColorFormat::HSL->value,
            ColorFormat::HSLA->value,
            ColorFormat::HWB->value,
            ColorFormat::LAB->value,
            ColorFormat::LABA->value,
            ColorFormat::LCH->value,
            ColorFormat::OKLCH->value,
            ColorFormat::RGB->value,
            ColorFormat::RGBA->value,
            ColorFormat::XYZ->value,
            ColorFormat::XYZA->value,
        ];
    }

    public static function fromString(string $color): self
    {
        $colorModule = new ColorModule();
        $data = $colorModule->parseColor($color);

        return new self($data);
    }

    public static function hsl(float $h, float $s, float $l, ?float $a = null): self
    {
        $a ??= self::DEFAULT_ALPHA;

        $data = [
            'h'      => $h,
            's'      => $s,
            'l'      => $l,
            'a'      => $a,
            'format' => $a < self::DEFAULT_ALPHA ? ColorFormat::HSLA->value : ColorFormat::HSL->value,
        ];

        return new self($data);
    }

    public static function hwb(float $h, float $w, float $bl, ?float $a = null): self
    {
        $a ??= self::DEFAULT_ALPHA;

        $data = [
            'h'      => $h,
            'w'      => $w,
            'bl'     => $bl,
            'a'      => $a,
            'format' => ColorFormat::HWB->value,
        ];

        return new self($data);
    }

    public static function lab(float $l, float $a, float $b, ?float $alpha = null): self
    {
        $alpha ??= self::DEFAULT_ALPHA;

        $data = [
            'lab_l'  => $l,
            'lab_a'  => $a,
            'lab_b'  => $b,
            'a'      => $alpha,
            'format' => $alpha < self::DEFAULT_ALPHA ? ColorFormat::LABA->value : ColorFormat::LAB->value,
        ];

        return new self($data);
    }

    public static function lch(float $l, float $c, float $h, ?float $a = null): self
    {
        $a ??= self::DEFAULT_ALPHA;

        $data = [
            'l'      => $l,
            'c'      => $c,
            'h'      => $h,
            'a'      => $a,
            'format' => ColorFormat::LCH->value,
        ];

        return new self($data);
    }

    public static function oklch(float $l, float $c, float $h, ?float $a = null): self
    {
        $a ??= self::DEFAULT_ALPHA;

        $data = [
            'l'      => $l,
            'c'      => $c,
            'h'      => $h,
            'a'      => $a,
            'format' => ColorFormat::OKLCH->value,
        ];

        return new self($data);
    }

    public static function rgb(float $r, float $g, float $b, ?float $a = null): self
    {
        $a ??= self::DEFAULT_ALPHA;

        $data = [
            'r'      => $r,
            'g'      => $g,
            'b'      => $b,
            'a'      => $a,
            'format' => $a < self::DEFAULT_ALPHA ? ColorFormat::RGBA->value : ColorFormat::RGB->value,
        ];

        return new self($data);
    }

    public static function xyz(float $x, float $y, float $z, ?float $a = null): self
    {
        $a ??= self::DEFAULT_ALPHA;

        $data = [
            'x'      => $x,
            'y'      => $y,
            'z'      => $z,
            'a'      => $a,
            'format' => $a < self::DEFAULT_ALPHA ? ColorFormat::XYZA->value : ColorFormat::XYZ->value,
        ];

        return new self($data);
    }
}
