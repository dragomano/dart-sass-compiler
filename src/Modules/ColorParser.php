<?php

declare(strict_types=1);

namespace DartSass\Modules;

use DartSass\Exceptions\CompilationException;

use function hexdec;
use function max;
use function min;
use function preg_match;
use function round;
use function rtrim;
use function str_contains;
use function str_repeat;
use function strlen;
use function strtolower;
use function substr;
use function trim;

use const M_PI;

enum ColorParser
{
    case HEX;
    case HEXA;
    case HSL;
    case HSLA;
    case HWB;
    case LAB;
    case LABA;
    case LCH;
    case OKLCH;
    case RGB;
    case RGBA;
    case XYZ;
    case XYZA;

    public function getPattern(): string
    {
        return ColorFormat::from(strtolower($this->name))->getPattern();
    }

    public function parse(string $color): ?array
    {
        if (! preg_match($this->getPattern(), $color, $matches)) {
            return null;
        }

        return match ($this) {
            self::HEX   => self::parseHexMatches($matches),
            self::HEXA  => self::parseHexaMatches($matches),
            self::HSL   => self::parseHslMatches($matches),
            self::HSLA  => self::parseHslaMatches($matches),
            self::HWB   => self::parseHwbMatches($matches),
            self::LAB   => self::parseLabMatches($matches),
            self::LABA  => self::parseLabaMatches($matches),
            self::LCH   => self::parseLchMatches($matches),
            self::OKLCH => self::parseOklchMatches($matches),
            self::RGB   => self::parseRgbMatches($matches),
            self::RGBA  => self::parseRgbaMatches($matches),
            self::XYZ   => self::parseXyzMatches($matches),
            self::XYZA  => self::parseXyzaMatches($matches),
        };
    }

    private static function parseHexMatches(array $matches): array
    {
        $hex = $matches[1];
        if (strlen($hex) === 3) {
            $r = hexdec(str_repeat($hex[0], 2));
            $g = hexdec(str_repeat($hex[1], 2));
            $b = hexdec(str_repeat($hex[2], 2));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }

        return [
            'r'      => $r,
            'g'      => $g,
            'b'      => $b,
            'a'      => ColorSerializer::ALPHA_MAX,
            'format' => ColorFormat::RGB->value,
        ];
    }

    private static function parseHexaMatches(array $matches): array
    {
        $hex = $matches[1];
        if (strlen($hex) === 4) {
            $r = hexdec(str_repeat($hex[0], 2));
            $g = hexdec(str_repeat($hex[1], 2));
            $b = hexdec(str_repeat($hex[2], 2));
            $a = hexdec(str_repeat($hex[3], 2)) / ColorSerializer::RGB_MAX;
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
            $a = hexdec(substr($hex, 6, 2)) / ColorSerializer::RGB_MAX;
        }

        return [
            'r'      => $r,
            'g'      => $g,
            'b'      => $b,
            'a'      => $a,
            'format' => ColorFormat::RGBA->value,
        ];
    }

    private static function parseHslMatches(array $matches): array
    {
        $h = self::parseHueValue($matches[1]);
        $s = self::parsePercentageValue($matches[2]);
        $l = self::parsePercentageValue($matches[3]);
        $a = isset($matches[4]) ? self::parseAlpha($matches[4]) : ColorSerializer::ALPHA_MAX;

        self::validateValueRange($s, 0, ColorSerializer::PERCENT_MAX, 'saturation');
        self::validateValueRange($l, 0, ColorSerializer::PERCENT_MAX, 'lightness');
        self::validateValueRange($a, ColorSerializer::ALPHA_MIN, ColorSerializer::ALPHA_MAX, 'alpha');

        return [
            'h'      => self::clamp($h, 0, ColorSerializer::HUE_MAX),
            's'      => self::clamp($s, 0, ColorSerializer::PERCENT_MAX),
            'l'      => self::clamp($l, 0, ColorSerializer::PERCENT_MAX),
            'a'      => self::clamp($a, ColorSerializer::ALPHA_MIN, ColorSerializer::ALPHA_MAX),
            'format' => $a < ColorSerializer::ALPHA_MAX ? ColorFormat::HSLA->value : ColorFormat::HSL->value,
        ];
    }

    private static function parseHslaMatches(array $matches): array
    {
        $h = self::parseHueValue($matches[1]);
        $s = self::parsePercentageValue($matches[2]);
        $l = self::parsePercentageValue($matches[3]);
        $a = self::parseAlpha($matches[4]);

        self::validateValueRange($s, 0, ColorSerializer::PERCENT_MAX, 'saturation');
        self::validateValueRange($l, 0, ColorSerializer::PERCENT_MAX, 'lightness');
        self::validateValueRange($a, ColorSerializer::ALPHA_MIN, ColorSerializer::ALPHA_MAX, 'alpha');

        return [
            'h'      => self::clamp($h, 0, ColorSerializer::HUE_MAX),
            's'      => self::clamp($s, 0, ColorSerializer::PERCENT_MAX),
            'l'      => self::clamp($l, 0, ColorSerializer::PERCENT_MAX),
            'a'      => self::clamp($a, ColorSerializer::ALPHA_MIN, ColorSerializer::ALPHA_MAX),
            'format' => ColorFormat::HSLA->value,
        ];
    }

    private static function parseHwbMatches(array $matches): array
    {
        $h  = self::parseHueValue($matches[1]);
        $w  = self::parsePercentageValue($matches[2]);
        $bl = self::parsePercentageValue($matches[3]);
        $a  = isset($matches[4]) ? self::parseAlpha($matches[4]) : ColorSerializer::ALPHA_MAX;

        self::validateValueRange($w, 0, ColorSerializer::PERCENT_MAX, 'whiteness');
        self::validateValueRange($bl, 0, ColorSerializer::PERCENT_MAX, 'blackness');
        self::validateValueRange($a, ColorSerializer::ALPHA_MIN, ColorSerializer::ALPHA_MAX, 'alpha');

        return [
            'h'      => self::clamp($h, 0, ColorSerializer::HUE_MAX),
            'w'      => self::clamp($w, 0, ColorSerializer::PERCENT_MAX),
            'bl'     => self::clamp($bl, 0, ColorSerializer::PERCENT_MAX),
            'a'      => self::clamp($a, ColorSerializer::ALPHA_MIN, ColorSerializer::ALPHA_MAX),
            'format' => ColorFormat::HWB->value,
        ];
    }

    private static function parseLabMatches(array $matches): array
    {
        $lRaw = $matches[1];

        $a = (float) $matches[2];
        $b = (float) $matches[3];
        $l = str_contains($lRaw, '%') ? self::parsePercentageValue($lRaw) : (float) $lRaw;

        return [
            'lab_l'  => $l,
            'lab_a'  => $a,
            'lab_b'  => $b,
            'a'      => ColorSerializer::ALPHA_MAX,
            'format' => ColorFormat::LAB->value,
        ];
    }

    private static function parseLabaMatches(array $matches): array
    {
        $lRaw = $matches[1];

        $a = (float) $matches[2];
        $b = (float) $matches[3];
        $l = str_contains($lRaw, '%') ? self::parsePercentageValue($lRaw) : (float) $lRaw;

        $alpha = self::parseAlpha($matches[4]);

        return [
            'lab_l'  => $l,
            'lab_a'  => $a,
            'lab_b'  => $b,
            'a'      => self::clamp($alpha, ColorSerializer::ALPHA_MIN, ColorSerializer::ALPHA_MAX),
            'format' => ColorFormat::LABA->value,
        ];
    }

    private static function parseLchMatches(array $matches): array
    {
        return self::parseLchLikeMatches(ColorFormat::LCH->value, $matches, 150);
    }

    private static function parseOklchMatches(array $matches): array
    {
        return self::parseLchLikeMatches(ColorFormat::OKLCH->value, $matches, 0.4);
    }

    private static function parseLchLikeMatches(string $format, array $matches, float $maxChroma): array
    {
        $lRaw = $matches[1];
        $hStr = $matches[3];

        $l = str_contains($lRaw, '%') ? self::parsePercentageValue($lRaw) : (float) $lRaw;
        $c = (float) $matches[2];
        $h = self::parseHueValue(trim($hStr));
        $a = isset($matches[4]) ? self::parseAlpha($matches[4]) : ColorSerializer::ALPHA_MAX;

        self::validateValueRange($a, ColorSerializer::ALPHA_MIN, ColorSerializer::ALPHA_MAX, 'alpha');

        return [
            'l'      => $l,
            'c'      => self::clamp($c, 0, $maxChroma),
            'h'      => self::clamp($h, 0, ColorSerializer::HUE_MAX),
            'a'      => self::clamp($a, ColorSerializer::ALPHA_MIN, ColorSerializer::ALPHA_MAX),
            'format' => $format,
        ];
    }

    private static function parseRgbMatches(array $matches): array
    {
        $rgb = self::parseRgbComponents($matches);
        $a   = isset($matches[4]) ? (float) $matches[4] : ColorSerializer::ALPHA_MAX;

        return [
            'r'      => $rgb['r'],
            'g'      => $rgb['g'],
            'b'      => $rgb['b'],
            'a'      => self::clamp($a, ColorSerializer::ALPHA_MIN, ColorSerializer::ALPHA_MAX),
            'format' => $a < ColorSerializer::ALPHA_MAX ? ColorFormat::RGBA->value : ColorFormat::RGB->value,
        ];
    }

    private static function parseRgbaMatches(array $matches): array
    {
        $rgb = self::parseRgbComponents($matches);
        $a   = (float) $matches[4];

        self::validateValueRange($a, ColorSerializer::ALPHA_MIN, ColorSerializer::ALPHA_MAX, 'alpha');

        return [
            'r'      => $rgb['r'],
            'g'      => $rgb['g'],
            'b'      => $rgb['b'],
            'a'      => self::clamp($a, ColorSerializer::ALPHA_MIN, ColorSerializer::ALPHA_MAX),
            'format' => ColorFormat::RGBA->value,
        ];
    }

    private static function parseXyzMatches(array $matches): array
    {
        $x = (float) $matches[1];
        $y = (float) $matches[2];
        $z = (float) $matches[3];
        $a = isset($matches[4]) ? self::parseAlpha($matches[4]) : ColorSerializer::ALPHA_MAX;

        self::validateValueRange($a, ColorSerializer::ALPHA_MIN, ColorSerializer::ALPHA_MAX, 'alpha');

        return [
            'x'      => self::clamp($x, -1000, 1000),
            'y'      => self::clamp($y, -1000, 1000),
            'z'      => self::clamp($z, -1000, 1000),
            'a'      => self::clamp($a, ColorSerializer::ALPHA_MIN, ColorSerializer::ALPHA_MAX),
            'format' => $a < ColorSerializer::ALPHA_MAX ? ColorFormat::XYZA->value : ColorFormat::XYZ->value,
        ];
    }

    private static function parseXyzaMatches(array $matches): array
    {
        $x = (float) $matches[1];
        $y = (float) $matches[2];
        $z = (float) $matches[3];
        $a = self::parseAlpha($matches[4]);

        return [
            'x'      => self::clamp($x, -1000, 1000),
            'y'      => self::clamp($y, -1000, 1000),
            'z'      => self::clamp($z, -1000, 1000),
            'a'      => self::clamp($a, ColorSerializer::ALPHA_MIN, ColorSerializer::ALPHA_MAX),
            'format' => ColorFormat::XYZA->value,
        ];
    }

    private static function parseHueValue(string $hueStr): float
    {
        $hue = match (true) {
            str_contains($hueStr, 'grad') => (float) rtrim($hueStr, 'grad') * ColorSerializer::HUE_MAX / 400,
            str_contains($hueStr, 'rad')  => (float) rtrim($hueStr, 'rad') * ColorSerializer::HUE_SHIFT / M_PI,
            str_contains($hueStr, 'turn') => (float) rtrim($hueStr, 'turn') * ColorSerializer::HUE_MAX,
            str_contains($hueStr, 'deg')  => (float) rtrim($hueStr, 'deg'),
            default                       => (float) $hueStr,
        };

        // Normalize hue to be within [0, 360) range
        while ($hue < 0) {
            $hue += ColorSerializer::HUE_MAX;
        }

        while ($hue >= ColorSerializer::HUE_MAX) {
            $hue -= ColorSerializer::HUE_MAX;
        }

        return $hue;
    }

    private static function parsePercentageValue(string $percentStr): float
    {
        return (float) rtrim($percentStr, '%');
    }

    private static function parseAlpha(string $alphaStr): float
    {
        if (str_contains($alphaStr, '%')) {
            return (float) rtrim($alphaStr, '%') / ColorSerializer::PERCENT_MAX;
        }

        return (float) $alphaStr;
    }

    private static function validateValueRange(float $value, float $min, float $max, string $name): void
    {
        if ($value < $min || $value > $max) {
            throw new CompilationException("Invalid $name value: $value");
        }
    }

    private static function clamp(float $value, float $min, float $max): float
    {
        return min(max($value, $min), $max);
    }

    private static function parseRgbComponents(array $matches): array
    {
        $multiplier = ColorSerializer::RGB_MAX / ColorSerializer::PERCENT_MAX;

        $r = str_contains($matches[1], '%')
            ? self::parsePercentageValue($matches[1]) * $multiplier
            : (float) $matches[1];
        $g = str_contains($matches[2], '%')
            ? self::parsePercentageValue($matches[2]) * $multiplier
            : (float) $matches[2];
        $b = str_contains($matches[3], '%')
            ? self::parsePercentageValue($matches[3]) * $multiplier
            : (float) $matches[3];

        // Clamp and round with intermediate precision
        $r = self::clamp($r, 0, ColorSerializer::RGB_MAX);
        $g = self::clamp($g, 0, ColorSerializer::RGB_MAX);
        $b = self::clamp($b, 0, ColorSerializer::RGB_MAX);

        return [
            'r' => (int) round(round($r, 10)),
            'g' => (int) round(round($g, 10)),
            'b' => (int) round(round($b, 10)),
        ];
    }
}
