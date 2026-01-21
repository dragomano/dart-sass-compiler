<?php

declare(strict_types=1);

namespace DartSass\Modules;

use DartSass\Values\SassColor;

use function in_array;
use function strtolower;

enum ColorFormat: string
{
    case HEX   = 'hex';
    case HEXA  = 'hexa';
    case HSL   = 'hsl';
    case HSLA  = 'hsla';
    case HWB   = 'hwb';
    case LAB   = 'lab';
    case LABA  = 'laba';
    case LCH   = 'lch';
    case OKLCH = 'oklch';
    case RGB   = 'rgb';
    case RGBA  = 'rgba';
    case XYZ   = 'xyz';
    case XYZA  = 'xyza';

    public function getPattern(): string
    {
        return match ($this) {
            self::HEX   => '/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/',
            self::HEXA  => '/^#([0-9a-fA-F]{4}|[0-9a-fA-F]{8})$/',
            self::HSL   => '/^hsl\(([-+]?\d+(?:\.\d+)?(?:deg|rad|grad|turn)?)[\s,]+(\d+(?:\.\d+)?)%[\s,]+(\d+(?:\.\d+)?)%\s*(?:\/\s*([0-1]?\.\d+|0|1|100%|\d{1,2}%))?\)$/',
            self::HSLA  => '/^hsla\(([-+]?\d+(?:\.\d+)?(?:deg|rad|grad|turn)?)[\s,]+(\d+(?:\.\d+)?)%[\s,]+(\d+(?:\.\d+)?)%[\s,]+([0-1]?\.\d+|0|1|100%|\d{1,2}%)?\)$/',
            self::HWB   => '/^hwb\(([-+]?\d+(?:\.\d+)?(?:deg|rad|grad|turn)?)[\s,]+(\d+(?:\.\d+)?)%[\s,]+(\d+(?:\.\d+)?)%\s*(?:\/\s*([0-1]?\.\d+|0|1|100%|\d{1,2}%))?\)$/',
            self::LAB   => '/^lab\((\d+(?:\.\d+)?%?)\s+([-+]?(?:\d+(?:\.\d+)?|\.\d+))\s+([-+]?(?:\d+(?:\.\d+)?|\.\d+))\)$/i',
            self::LABA  => '/^lab\((\d+(?:\.\d+)?%?)\s+([-+]?(?:\d+(?:\.\d+)?|\.\d+))\s+([-+]?(?:\d+(?:\.\d+)?|\.\d+))\s*\/\s*([0-1]?(?:\.\d+)?|\d{1,3}%)\)$/i',
            self::LCH   => '/^lch\((\d+(?:\.\d+)?%?)\s+(\d+(?:\.\d+)?)\s+(\d+(?:\.\d+)?(?:deg|rad|grad|turn)?)\s*(?:\/\s*([0-1]?\.\d+|0|1|100%|\d{1,2}%))?\)$/',
            self::OKLCH => '/^oklch\((\d+(?:\.\d+)?%?)\s+(\d+(?:\.\d+)?)\s+(\d+(?:\.\d+)?(?:deg|rad|grad|turn)?)\s*(?:\/\s*([0-1]?\.\d+|0|1|100%|\d{1,2}%))?\)$/',
            self::RGB   => '/^rgb\((\d+(?:\.\d+)?%?)[\s,]+(\d+(?:\.\d+)?%?)[\s,]+(\d+(?:\.\d+)?%?)\s*(?:\/\s*([0-1]?\.\d+|0|1))?\)$/',
            self::RGBA  => '/^rgba\((\d+(?:\.\d+)?%?)[\s,]+(\d+(?:\.\d+)?%?)[\s,]+(\d+(?:\.\d+)?%?)[\s,]+([0-1]?\.\d+|0|1)\)$/',
            self::XYZ   => '/^color\(xyz\s+([-+]?(?:\d+(?:\.\d+)?|\.\d+))\s+([-+]?(?:\d+(?:\.\d+)?|\.\d+))\s+([-+]?(?:\d+(?:\.\d+)?|\.\d+))\s*(?:\/\s*([0-1]?(?:\.\d+)?|\d+%))?\)$/i',
            self::XYZA  => '/^color\(xyz\s+([-+]?(?:\d+(?:\.\d+)?|\.\d+))\s+([-+]?(?:\d+(?:\.\d+)?|\.\d+))\s+([-+]?(?:\d+(?:\.\d+)?|\.\d+))\s+\/\s*([0-1]?(?:\.\d+)?|\d+%)\)$/i',
        };
    }

    public function format(array $colorData): string
    {
        $sassColor = new SassColor($colorData);

        return ColorSerializer::format($this, $sassColor);
    }

    public function isPolar(): bool
    {
        return match ($this) {
            self::HSL,
            self::HSLA,
            self::HWB,
            self::LCH,
            self::OKLCH => true,
            default     => false,
        };
    }

    public function isLegacy(): bool
    {
        return match ($this) {
            self::HSL,
            self::HSLA,
            self::HWB,
            self::RGB,
            self::RGBA => true,
            default    => false,
        };
    }

    public function getChannels(): array
    {
        return match ($this) {
            self::HSL,
            self::HSLA  => ['hue', 'h', 'saturation', 's', 'lightness', 'l', 'alpha', 'a'],
            self::HWB   => ['hue', 'h', 'whiteness', 'w', 'blackness', 'bl', 'alpha', 'a'],
            self::LAB,
            self::LABA  => ['lab_l', 'lab_a', 'lab_b', 'alpha'],
            self::LCH,
            self::OKLCH => ['lightness', 'l', 'chroma', 'c', 'hue', 'h', 'alpha', 'a'],
            self::RGB,
            self::RGBA  => ['red', 'r', 'green', 'g', 'blue', 'b', 'alpha', 'a'],
            self::XYZ,
            self::XYZA  => ['x', 'y', 'z', 'alpha', 'a'],
            default     => ['alpha', 'a'],
        };
    }

    public function hasChannel(string $channel): bool
    {
        $channel = strtolower($channel);

        return in_array($channel, $this->getChannels(), true);
    }

    public function getPrimaryChannels(): array
    {
        return match ($this) {
            self::HSL,
            self::HSLA  => ['hue', 'saturation', 'lightness'],
            self::HWB   => ['hue', 'whiteness', 'blackness'],
            self::LAB,
            self::LABA  => ['l', 'a', 'b'],
            self::LCH,
            self::OKLCH => ['lightness', 'chroma', 'hue'],
            self::RGB,
            self::RGBA  => ['red', 'green', 'blue'],
            self::XYZ,
            self::XYZA  => ['x', 'y', 'z'],
            default     => [],
        };
    }

    public function isCompatibleWith(?self $other): bool
    {
        if ($other === null) {
            return false;
        }

        if ($this === $other) {
            return true;
        }

        return $this->getBaseFormat() === $other->getBaseFormat();
    }

    public function getBaseFormat(): self
    {
        return match ($this) {
            self::HSLA => self::HSL,
            self::LABA => self::LAB,
            self::RGBA => self::RGB,
            self::XYZA => self::XYZ,
            default    => $this,
        };
    }
}
