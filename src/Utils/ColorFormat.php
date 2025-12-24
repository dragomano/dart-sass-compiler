<?php

declare(strict_types=1);

namespace DartSass\Utils;

enum ColorFormat: string
{
    case HEX = 'hex';
    case HEXA = 'hexa';
    case HSL = 'hsl';
    case HSLA = 'hsla';
    case HWB = 'hwb';
    case LCH = 'lch';
    case OKLCH = 'oklch';
    case RGB = 'rgb';
    case RGBA = 'rgba';

    public function getPattern(): string
    {
        return match ($this) {
            self::HEX,
            self::HEXA  => '/^#([0-9a-fA-F]{3,8})$/',
            self::HSL   => '/^hsl\((\d+(?:\.\d+)?(?:deg|rad|grad|turn)?)[\s,]+(\d+(?:\.\d+)?)%[\s,]+(\d+(?:\.\d+)?)%\s*(?:\/\s*([0-1]?\.\d+|0|1|100%|\d{1,2}%))?\)$/',
            self::HSLA  => '/^hsla\((\d+(?:\.\d+)?(?:deg|rad|grad|turn)?)[\s,]+(\d+(?:\.\d+)?)%[\s,]+(\d+(?:\.\d+)?)%[\s,]+([0-1]?\.\d+|0|1|100%|\d{1,2}%)?\)$/',
            self::HWB   => '/^hwb\((\d+(?:\.\d+)?(?:deg|rad|grad|turn)?)[\s,]+(\d+(?:\.\d+)?)%[\s,]+(\d+(?:\.\d+)?)%\s*(?:\/\s*([0-1]?\.\d+|0|1|100%|\d{1,2}%))?\)$/',
            self::LCH   => '/^lch\((\d+(?:\.\d+)?%?)\s+(\d+(?:\.\d+)?)\s+(\d+(?:\.\d+)?(?:deg|rad|grad|turn)?)\s*(?:\/\s*([0-1]?\.\d+|0|1|100%|\d{1,2}%))?\)$/',
            self::OKLCH => '/^oklch\((\d+(?:\.\d+)?%?)\s+(\d+(?:\.\d+)?)\s+(\d+(?:\.\d+)?(?:deg|rad|grad|turn)?)\s*(?:\/\s*([0-1]?\.\d+|0|1|100%|\d{1,2}%))?\)$/',
            self::RGB   => '/^rgb\((\d+(?:\.\d+)?%?)[\s,]+(\d+(?:\.\d+)?%?)[\s,]+(\d+(?:\.\d+)?%?)\s*(?:\/\s*([0-1]?\.\d+|0|1))?\)$/',
            self::RGBA  => '/^rgba\((\d+(?:\.\d+)?%?)[\s,]+(\d+(?:\.\d+)?%?)[\s,]+(\d+(?:\.\d+)?%?)[\s,]+([0-1]?\.\d+|0|1)\)$/',
        };
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
            self::RGB,
            self::RGBA,
            self::HSL,
            self::HSLA,
            self::HWB => true,
            default   => false,
        };
    }

    public function getChannels(): array
    {
        return match ($this) {
            self::RGB,
            self::RGBA => ['red', 'r', 'green', 'g', 'blue', 'b', 'alpha', 'a'],
            self::HSL,
            self::HSLA => ['hue', 'h', 'saturation', 's', 'lightness', 'l', 'alpha', 'a'],
            self::HWB => ['hue', 'h', 'whiteness', 'w', 'blackness', 'bl', 'alpha', 'a'],
            self::LCH,
            self::OKLCH => ['lightness', 'l', 'chroma', 'c', 'hue', 'h', 'alpha', 'a'],
            default => ['alpha', 'a'],
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
            self::RGB,
            self::RGBA => ['red', 'green', 'blue'],
            self::HSL,
            self::HSLA => ['hue', 'saturation', 'lightness'],
            self::HWB => ['hue', 'whiteness', 'blackness'],
            self::LCH,
            self::OKLCH => ['lightness', 'chroma', 'hue'],
            default => [],
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
            self::RGBA => self::RGB,
            self::HSLA => self::HSL,
            default    => $this,
        };
    }
}
