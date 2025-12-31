<?php

declare(strict_types=1);

namespace DartSass\Modules;

use DartSass\Exceptions\CompilationException;
use InvalidArgumentException;

use function abs;
use function array_merge;
use function array_unique;
use function fmod;
use function hexdec;
use function implode;
use function in_array;
use function max;
use function min;
use function preg_match;
use function round;
use function rtrim;
use function sprintf;
use function str_contains;
use function str_repeat;
use function strlen;
use function strtolower;
use function substr;
use function trim;

use const M_PI;

class ColorModule
{
    private const COLOR_ADJUST_PARAMS = [
        '$red'        => true,
        '$green'      => true,
        '$blue'       => true,
        '$hue'        => true,
        '$saturation' => true,
        '$lightness'  => true,
        '$alpha'      => true,
        '$whiteness'  => true,
        '$blackness'  => true,
        '$x'          => true,
        '$y'          => true,
        '$z'          => true,
        '$chroma'     => true,
        '$space'      => true,
    ];

    public function adjust(string $color, array $adjustments): string
    {
        $colorData = $this->parseColor($color);
        $colorData = $this->applyAdjustments($colorData, $adjustments);

        return $this->formatColor($colorData);
    }

    public function change(string $color, array $adjustments): string
    {
        $colorData = $this->parseColor($color);
        $colorData = $this->applyChanges($colorData, $adjustments);

        return $this->formatColor($colorData);
    }

    public function channel(string $color, string $channel, ?string $space = null): string
    {
        $colorData = $this->parseColor($color);
        $channel   = strtolower($channel);

        if ($space !== null) {
            $colorData = $this->convertToSpace($colorData, strtolower($space));
        }

        $this->validateChannel($channel);

        $dataKey = $this->channelToDataKey($channel);

        $targetSpace = match ($channel) {
            'hue', 'h',
            'saturation', 's',
            'lightness', 'l'          => ColorFormat::HSL->value,
            'whiteness', 'w',
            'blackness','bl'          => ColorFormat::HWB->value,
            'chroma', 'c'             => ColorFormat::LCH->value,
            'x', 'y', 'z'             => ColorFormat::XYZ->value,
            'lab_l', 'lab_a', 'lab_b' => ColorFormat::LAB->value,
            default                   => ColorFormat::RGB->value,
        };

        $value = $colorData[$dataKey] ?? $this->convertToSpace($colorData, $targetSpace)[$dataKey];

        if ($channel === 'alpha' || $channel === 'a') {
            return (string) $value;
        }

        if (in_array($channel, [
            'saturation', 's',
            'lightness', 'l',
            'whiteness', 'w',
            'blackness', 'bl',
            'lab_l',
        ], true)) {
            return round($value, 10) . '%';
        }

        if (in_array($channel, ['lab_a', 'lab_b'], true)) {
            return (string) round($value, 10);
        }

        if (in_array($channel, ['hue', 'h'], true)) {
            $rounded = round($value, 10);

            return $rounded == 0 ? (string) $rounded : $rounded . 'deg';
        }

        if (in_array($channel, ['chroma', 'c'], true)) {
            return (string) round($value, 2);
        }

        return (string) round($value);
    }

    public function complement(string $color, ?string $space = null): string
    {
        $colorData = $this->parseColor($color);
        $space ??= ColorFormat::HSL->value;

        $spaceFormat = ColorFormat::tryFrom($space);
        if ($spaceFormat === null || ! $spaceFormat->isPolar()) {
            throw new CompilationException(
                "Color space '$space' is not a polar color space. Use hsl, hwb, lch, or oklch."
            );
        }

        $colorData = ColorSerializer::ensureRgbFormat($colorData);

        switch ($space) {
            case ColorFormat::HWB->value:
                $hwb = ColorConverter::RGB->toHwb($colorData['r'], $colorData['g'], $colorData['b']);
                $hue = fmod($hwb['h'] + ColorSerializer::HUE_SHIFT, ColorSerializer::HUE_MAX);
                $rgb = ColorConverter::HWB->toRgb($hue, $hwb['w'], $hwb['bl']);

                break;

            case ColorFormat::LCH->value:
                $lch = ColorConverter::RGB->toLch($colorData['r'], $colorData['g'], $colorData['b']);
                $hue = fmod($lch['h'] + ColorSerializer::HUE_SHIFT, ColorSerializer::HUE_MAX);
                $rgb = ColorConverter::LCH->toRgb($lch['l'], $lch['c'], $hue);

                break;

            case ColorFormat::OKLCH->value:
                $oklch = ColorConverter::RGB->toOklch($colorData['r'], $colorData['g'], $colorData['b']);
                $hue = fmod($oklch['h'] + ColorSerializer::HUE_SHIFT, ColorSerializer::HUE_MAX);
                $rgb = ColorConverter::OKLCH->toRgb($oklch['l'], $oklch['c'], $hue);

                break;

            default:
                $hsl = ColorConverter::RGB->toHsl($colorData['r'], $colorData['g'], $colorData['b']);
                $hue = fmod($hsl['h'] + ColorSerializer::HUE_SHIFT, ColorSerializer::HUE_MAX);
                $rgb = ColorConverter::HSL->toRgb($hue, $hsl['s'], $hsl['l']);

                break;
        }

        $alpha = $colorData['a'] ?? ColorSerializer::ALPHA_MAX;

        // Ensure high precision for alpha channel preservation
        $alpha = round($alpha, 15);

        return $this->formatColor(
            array_merge($rgb, [
                'a'      => $this->clamp($alpha, ColorSerializer::ALPHA_MIN, ColorSerializer::ALPHA_MAX),
                'format' => ColorFormat::RGB->value,
            ])
        );
    }

    public function grayscale(string $color): string
    {
        $colorData = $this->parseColor($color);

        $hsl = ColorConverter::RGB->toHsl($colorData['r'], $colorData['g'], $colorData['b']);
        $rgb = ColorConverter::HSL->toRgb($hsl['h'], 0, $hsl['l']);

        return $this->formatColor(
            array_merge($rgb, [
                'a'      => $colorData['a'] ?? ColorSerializer::ALPHA_MAX,
                'format' => ColorFormat::RGB->value,
            ])
        );
    }

    public function ieHexStr(string $color): string
    {
        $colorData = $this->parseColor($color);

        $a = (int) round(($colorData['a'] ?? ColorSerializer::ALPHA_MAX) * ColorSerializer::RGB_MAX);
        $r = (int) round($colorData['r']);
        $g = (int) round($colorData['g']);
        $b = (int) round($colorData['b']);

        return sprintf('#%02X%02X%02X%02X', $a, $r, $g, $b);
    }

    public function invert(string $color, int $weight = 100, ?string $space = null): string
    {
        $colorData = $this->parseColor($color);
        $space ??= ColorFormat::RGB->value;
        $weightFactor = $this->clamp(
            $weight / ColorSerializer::PERCENT_MAX,
            ColorSerializer::ALPHA_MIN,
            ColorSerializer::ALPHA_MAX
        );

        switch ($space) {
            case ColorFormat::HWB->value:
                $hwb = ColorConverter::RGB->toHwb($colorData['r'], $colorData['g'], $colorData['b']);
                $invertedHwb = ColorConverter::HWB->toRgb(
                    ($hwb['h'] + ColorSerializer::HUE_SHIFT) % ColorSerializer::HUE_MAX,
                    $hwb['bl'],
                    $hwb['w']
                );
                $inverted = ['r' => $invertedHwb['r'], 'g' => $invertedHwb['g'], 'b' => $invertedHwb['b']];

                break;

            case ColorFormat::HSL->value:
                $hsl = ColorConverter::RGB->toHsl($colorData['r'], $colorData['g'], $colorData['b']);
                $invertedHue = ($hsl['h'] + ColorSerializer::HUE_SHIFT) % ColorSerializer::HUE_MAX;
                $invertedHsl = ColorConverter::HSL->toRgb($invertedHue, $hsl['s'], $hsl['l']);
                $inverted = ['r' => $invertedHsl['r'], 'g' => $invertedHsl['g'], 'b' => $invertedHsl['b']];

                break;

            default:
                $colorData = ColorSerializer::ensureRgbFormat($colorData);
                $inverted = [
                    'r' => ColorSerializer::RGB_MAX - $colorData['r'],
                    'g' => ColorSerializer::RGB_MAX - $colorData['g'],
                    'b' => ColorSerializer::RGB_MAX - $colorData['b'],
                ];

                break;
        }

        $r = $colorData['r'] * (1 - $weightFactor) + $inverted['r'] * $weightFactor;
        $g = $colorData['g'] * (1 - $weightFactor) + $inverted['g'] * $weightFactor;
        $b = $colorData['b'] * (1 - $weightFactor) + $inverted['b'] * $weightFactor;

        return $this->formatColor([
            'r'      => round($r),
            'g'      => round($g),
            'b'      => round($b),
            'a'      => $colorData['a'] ?? ColorSerializer::ALPHA_MAX,
            'format' => ColorFormat::RGB->value,
        ]);
    }

    public function isLegacy(string $color): string
    {
        $colorData = $this->parseColor($color);
        $format    = $colorData['format'] ?? ColorFormat::RGB->value;

        $formatEnum = ColorFormat::tryFrom($format);
        $isLegacy   = $formatEnum?->isLegacy() ?? false;

        return $isLegacy ? 'true' : 'false';
    }

    public function isMissing(string $color, string $channel): string
    {
        $colorData = $this->parseColor($color);
        $channel   = strtolower(trim($channel, '"\''));

        if ($channel === 'alpha' || $channel === 'a') {
            return 'false';
        }

        $this->validateChannel($channel);

        $dataKey = $this->channelToDataKey($channel);
        $missing = ! isset($colorData[$dataKey]);

        return $missing ? 'true' : 'false';
    }

    public function isPowerless(string $color, string $channel, ?string $space = null): string
    {
        $channel = strtolower(trim($channel, '"\''));

        $this->validateChannel($channel);

        if ($space !== null) {
            $spaceFormat = ColorFormat::tryFrom(strtolower($space));

            if ($spaceFormat === null) {
                throw new CompilationException("Unknown color space: $space");
            }

            if (! $spaceFormat->hasChannel($channel)) {
                $validChannels = implode(', ', $spaceFormat->getPrimaryChannels());

                throw new CompilationException(
                    "Channel '$channel' is not valid for color space '$space'. "
                    . "Valid channels: $validChannels, alpha"
                );
            }
        }

        $colorData = $this->parseColor($color);
        $colorData = ColorSerializer::ensureRgbFormat($colorData);

        $r = $colorData['r'];
        $g = $colorData['g'];
        $b = $colorData['b'];
        $a = $colorData['a'] ?? ColorSerializer::ALPHA_MAX;

        $hsl = ColorConverter::RGB->toHsl($r, $g, $b);

        return match ($channel) {
            'hue', 'h' => ($hsl['s'] <= ColorSerializer::ALPHA_MIN) ? 'true' : 'false',
            'saturation', 's' => ($hsl['l'] <= ColorSerializer::ALPHA_MIN || $hsl['l'] >= ColorSerializer::PERCENT_MAX)
                ? 'true'
                : 'false',
            'red', 'r',
            'green', 'g',
            'blue', 'b',
            'whiteness', 'w',
            'blackness', 'bl' => ($a <= ColorSerializer::ALPHA_MIN) ? 'true' : 'false',
            default => 'false',
        };
    }

    public function mix(string $color1, string $color2, float $weight = 0.5): string
    {
        $c1 = $this->parseColor($color1);
        $c2 = $this->parseColor($color2);

        if ($weight > 1) {
            $weight /= 100;
        }

        $weight = $this->clamp($weight, ColorSerializer::ALPHA_MIN, ColorSerializer::ALPHA_MAX);

        $c1 = ColorSerializer::ensureRgbFormat($c1);
        $c2 = ColorSerializer::ensureRgbFormat($c2);

        $r = round($c1['r'] * $weight + $c2['r'] * (1 - $weight));
        $g = round($c1['g'] * $weight + $c2['g'] * (1 - $weight));
        $b = round($c1['b'] * $weight + $c2['b'] * (1 - $weight));
        $a = $c1['a'] * $weight + $c2['a'] * (1 - $weight);

        return $this->formatColor([
            'r'      => $this->clamp($r, 0, ColorSerializer::RGB_MAX),
            'g'      => $this->clamp($g, 0, ColorSerializer::RGB_MAX),
            'b'      => $this->clamp($b, 0, ColorSerializer::RGB_MAX),
            'a'      => $this->clamp($a, ColorSerializer::ALPHA_MIN, ColorSerializer::ALPHA_MAX),
            'format' => $c1['a'] < ColorSerializer::ALPHA_MAX || $c2['a'] < ColorSerializer::ALPHA_MAX
                ? ColorFormat::RGBA->value
                : ColorFormat::RGB->value,
        ]);
    }

    public function same(string $color1, string $color2): string
    {
        $color1Data = $this->parseColor($color1);
        $color2Data = $this->parseColor($color2);

        $color1Data = ColorSerializer::ensureRgbFormat($color1Data);
        $color2Data = ColorSerializer::ensureRgbFormat($color2Data);

        $rMatch = abs($color1Data['r'] - $color2Data['r']) < 0.5;
        $gMatch = abs($color1Data['g'] - $color2Data['g']) < 0.5;
        $bMatch = abs($color1Data['b'] - $color2Data['b']) < 0.5;
        $aMatch = abs(
            ($color1Data['a'] ?? ColorSerializer::ALPHA_MAX) - ($color2Data['a'] ?? ColorSerializer::ALPHA_MAX)
        ) < 0.01;

        return ($rMatch && $gMatch && $bMatch && $aMatch) ? 'true' : 'false';
    }

    public function scale(string $color, array $adjustments): string
    {
        $colorData = $this->parseColor($color);
        $colorData = $this->applyScaling($colorData, $adjustments);

        return $this->formatColor($colorData);
    }

    public function space(string $color): string
    {
        $colorData = $this->parseColor($color);

        return $this->getColorSpace($colorData['format'] ?? ColorFormat::RGB->value);
    }

    public function toGamut(string $color, ?string $space = null, ?string $method = 'clip'): string
    {
        if ($method !== 'clip') {
            throw new CompilationException("Only 'clip' method is currently supported");
        }

        $colorData      = $this->parseColor($color);
        $originalFormat = $colorData['format'];
        $originalSpace  = $this->getColorSpace($originalFormat);

        $space = $space ? strtolower($space) : $originalSpace;

        $inTargetSpace = $this->convertToSpace($colorData, $space);

        match ($space) {
            ColorFormat::RGB->value => (function () use (&$inTargetSpace): void {
                $inTargetSpace['r'] = round($this->clamp($inTargetSpace['r'], 0, ColorSerializer::RGB_MAX));
                $inTargetSpace['g'] = round($this->clamp($inTargetSpace['g'], 0, ColorSerializer::RGB_MAX));
                $inTargetSpace['b'] = round($this->clamp($inTargetSpace['b'], 0, ColorSerializer::RGB_MAX));
            })(),
            ColorFormat::HSL->value => (function () use (&$inTargetSpace): void {
                $inTargetSpace['h'] = fmod($inTargetSpace['h'], ColorSerializer::HUE_MAX);
                $inTargetSpace['s'] = $this->clamp($inTargetSpace['s'], 0, ColorSerializer::PERCENT_MAX);
                $inTargetSpace['l'] = $this->clamp($inTargetSpace['l'], 0, ColorSerializer::PERCENT_MAX);
            })(),
            ColorFormat::HWB->value => (function () use (&$inTargetSpace): void {
                $inTargetSpace['h']  = fmod($inTargetSpace['h'], ColorSerializer::HUE_MAX);
                $inTargetSpace['w']  = $this->clamp($inTargetSpace['w'], 0, ColorSerializer::PERCENT_MAX);
                $inTargetSpace['bl'] = $this->clamp($inTargetSpace['bl'], 0, ColorSerializer::PERCENT_MAX);

                $sum = $inTargetSpace['w'] + $inTargetSpace['bl'];

                if ($sum > ColorSerializer::PERCENT_MAX) {
                    $inTargetSpace['w']  = ($inTargetSpace['w'] / $sum) * ColorSerializer::PERCENT_MAX;
                    $inTargetSpace['bl'] = ($inTargetSpace['bl'] / $sum) * ColorSerializer::PERCENT_MAX;
                }
            })(),
            default => null,
        };

        $inTargetSpace['a'] = $this->clamp(
            $inTargetSpace['a'] ?? ColorSerializer::ALPHA_MAX,
            ColorSerializer::ALPHA_MIN,
            ColorSerializer::ALPHA_MAX
        );

        if ($space !== $originalSpace) {
            return $this->toSpace($this->formatColor($inTargetSpace), $originalSpace);
        }

        // Ensure the format is correct for the target space
        $inTargetSpace['format'] = $originalFormat;

        // Ensure all required keys are present for the format
        if ($space === ColorFormat::RGB->value || $space === ColorFormat::RGBA->value) {
            $inTargetSpace['r'] ??= 0;
            $inTargetSpace['g'] ??= 0;
            $inTargetSpace['b'] ??= 0;
        }

        return $this->formatColor($inTargetSpace);
    }

    public function toSpace(string $color, ?string $space = null): string
    {
        $colorData = $this->parseColor($color);

        if ($space === null) {
            return $this->formatColor($colorData);
        }

        $converted = $this->convertToSpace($colorData, strtolower($space));

        return $this->formatColor($converted);
    }

    public function adjustHue(string $color, float $amount): string
    {
        return $this->adjust($color, ['$hue' => $amount]);
    }

    public function alpha(string $color): string
    {
        return $this->channel($color, 'alpha');
    }

    public function opacity(string $color): string
    {
        return $this->alpha($color);
    }

    public function red(string $color): string
    {
        return $this->channel($color, 'red');
    }

    public function green(string $color): string
    {
        return $this->channel($color, 'green');
    }

    public function blue(string $color): string
    {
        return $this->channel($color, 'blue');
    }

    public function hue(string $color): string
    {
        return $this->channel($color, 'hue');
    }

    public function blackness(string $color): string
    {
        return $this->channel($color, 'blackness');
    }

    public function lightness(string $color): string
    {
        return $this->channel($color, 'lightness');
    }

    public function whiteness(string $color): string
    {
        return $this->channel($color, 'whiteness');
    }

    public function saturation(string $color): string
    {
        return $this->channel($color, 'saturation');
    }

    public function lighten(string $color, float $amount): string
    {
        return $this->adjust($color, ['$lightness' => $amount]);
    }

    public function darken(string $color, float $amount): string
    {
        return $this->lighten($color, -$amount);
    }

    public function saturate(string $color, float $amount): string
    {
        return $this->adjust($color, ['$saturation' => $amount]);
    }

    public function desaturate(string $color, float $amount): string
    {
        return $this->saturate($color, -$amount);
    }

    public function opacify(string $color, float $amount): string
    {
        return $this->adjust($color, ['$alpha' => $amount]);
    }

    public function transparentize(string $color, float $amount): string
    {
        return $this->opacify($color, -$amount);
    }

    public function fadeIn(string $color, float $amount): string
    {
        return $this->opacify($color, $amount);
    }

    public function fadeOut(string $color, float $amount): string
    {
        return $this->transparentize($color, $amount);
    }

    public function hsl(float $h, float $s, float $l, ?float $a = null): string
    {
        return (string) SassColor::hsl($h, $s, $l, $a);
    }

    public function hwb(float $h, float $w, float $bl, ?float $a = null): string
    {
        return (string) SassColor::hwb($h, $w, $bl, $a);
    }

    public function lab(float $l, float $a, float $b, ?float $alpha = null): string
    {
        return (string) SassColor::lab($l, $a, $b, $alpha);
    }

    public function lch(float $l, float $c, float $h, ?float $a = null): string
    {
        return (string) SassColor::lch($l, $c, $h, $a);
    }

    public function oklch(float $l, float $c, float $h, ?float $a = null): string
    {
        return (string) SassColor::oklch($l, $c, $h, $a);
    }

    public function parseColor(string $color): array
    {
        $color = trim($color);

        if (isset(ColorSerializer::NAMED_COLORS[$color])) {
            return $this->parseHexColor(substr(ColorSerializer::NAMED_COLORS[$color], 1));
        }

        foreach (ColorFormat::cases() as $format) {
            if (preg_match($format->getPattern(), $color, $matches)) {
                return match ($format) {
                    ColorFormat::HEX   => $this->parseHexColor($matches[1]),
                    ColorFormat::HEXA  => $this->parseHexaColor($matches[1]),
                    ColorFormat::HSL   => $this->parseHslColor($matches),
                    ColorFormat::HSLA  => $this->parseHslaColor($matches),
                    ColorFormat::HWB   => $this->parseHwbColor($matches),
                    ColorFormat::LAB   => $this->parseLabColor($matches),
                    ColorFormat::LABA  => $this->parseLabaColor($matches),
                    ColorFormat::LCH   => $this->parseLchColor($matches),
                    ColorFormat::OKLCH => $this->parseOklchColor($matches),
                    ColorFormat::RGB   => $this->parseRgbColor($matches),
                    ColorFormat::RGBA  => $this->parseRgbaColor($matches),
                    ColorFormat::XYZ   => $this->parseXyzColor($matches),
                    ColorFormat::XYZA  => $this->parseXyzaColor($matches),
                };
            }
        }

        throw new CompilationException("Invalid color format: $color");
    }

    public function formatColor(array $colorData): string
    {
        $sassColor = new SassColor($colorData);

        return (string) $sassColor;
    }

    private function parseHexColor(string $hex): array
    {
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

    private function parseHexaColor(string $hex): array
    {
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

    private function parseRgbComponents(array $matches): array
    {
        $r = str_contains($matches[1], '%') ? $this->parsePercentageValue($matches[1]) * 2.55 : (float) $matches[1];
        $g = str_contains($matches[2], '%') ? $this->parsePercentageValue($matches[2]) * 2.55 : (float) $matches[2];
        $b = str_contains($matches[3], '%') ? $this->parsePercentageValue($matches[3]) * 2.55 : (float) $matches[3];

        // Clamp values to valid RGB range before rounding
        $r = $this->clamp($r, 0, ColorSerializer::RGB_MAX);
        $g = $this->clamp($g, 0, ColorSerializer::RGB_MAX);
        $b = $this->clamp($b, 0, ColorSerializer::RGB_MAX);

        return [
            'r' => (int) round($r),
            'g' => (int) round($g),
            'b' => (int) round($b),
        ];
    }

    private function parseHslColor(array $matches): array
    {
        $h = $this->parseHueValue($matches[1]);
        $s = $this->parsePercentageValue($matches[2]);
        $l = $this->parsePercentageValue($matches[3]);
        $a = isset($matches[4]) ? $this->parseAlpha($matches[4]) : ColorSerializer::ALPHA_MAX;

        $this->validateValueRange($s, 0, ColorSerializer::PERCENT_MAX, 'saturation');
        $this->validateValueRange($l, 0, ColorSerializer::PERCENT_MAX, 'lightness');
        $this->validateValueRange($a, ColorSerializer::ALPHA_MIN, ColorSerializer::ALPHA_MAX, 'alpha');

        return [
            'h'      => $this->clamp($h, 0, ColorSerializer::HUE_MAX),
            's'      => $this->clamp($s, 0, ColorSerializer::PERCENT_MAX),
            'l'      => $this->clamp($l, 0, ColorSerializer::PERCENT_MAX),
            'a'      => $this->clamp($a, ColorSerializer::ALPHA_MIN, ColorSerializer::ALPHA_MAX),
            'format' => $a < ColorSerializer::ALPHA_MAX ? ColorFormat::HSLA->value : ColorFormat::HSL->value,
        ];
    }

    private function parseHslaColor(array $matches): array
    {
        $h = $this->parseHueValue($matches[1]);
        $s = $this->parsePercentageValue($matches[2]);
        $l = $this->parsePercentageValue($matches[3]);
        $a = $this->parseAlpha($matches[4]);

        $this->validateValueRange($s, 0, ColorSerializer::PERCENT_MAX, 'saturation');
        $this->validateValueRange($l, 0, ColorSerializer::PERCENT_MAX, 'lightness');
        $this->validateValueRange($a, ColorSerializer::ALPHA_MIN, ColorSerializer::ALPHA_MAX, 'alpha');

        return [
            'h'      => $this->clamp($h, 0, ColorSerializer::HUE_MAX),
            's'      => $this->clamp($s, 0, ColorSerializer::PERCENT_MAX),
            'l'      => $this->clamp($l, 0, ColorSerializer::PERCENT_MAX),
            'a'      => $this->clamp($a, ColorSerializer::ALPHA_MIN, ColorSerializer::ALPHA_MAX),
            'format' => ColorFormat::HSLA->value,
        ];
    }

    private function parseHwbColor(array $matches): array
    {
        $h  = $this->parseHueValue($matches[1]);
        $w  = $this->parsePercentageValue($matches[2]);
        $bl = $this->parsePercentageValue($matches[3]);
        $a  = isset($matches[4]) ? $this->parseAlpha($matches[4]) : ColorSerializer::ALPHA_MAX;

        $this->validateValueRange($w, 0, ColorSerializer::PERCENT_MAX, 'whiteness');
        $this->validateValueRange($bl, 0, ColorSerializer::PERCENT_MAX, 'blackness');
        $this->validateValueRange($a, ColorSerializer::ALPHA_MIN, ColorSerializer::ALPHA_MAX, 'alpha');

        return [
            'h'      => (int) round($this->clamp($h, 0, ColorSerializer::HUE_MAX)),
            'w'      => (int) round($this->clamp($w, 0, ColorSerializer::PERCENT_MAX)),
            'bl'     => (int) round($this->clamp($bl, 0, ColorSerializer::PERCENT_MAX)),
            'a'      => $this->clamp($a, ColorSerializer::ALPHA_MIN, ColorSerializer::ALPHA_MAX),
            'format' => ColorFormat::HWB->value,
        ];
    }

    private function parseLabColor(array $matches): array
    {
        $lRaw = $matches[1];
        $a    = (float) $matches[2];
        $b    = (float) $matches[3];

        $l = str_contains($lRaw, '%') ? $this->parsePercentageValue($lRaw) : (float) $lRaw * ColorSerializer::PERCENT_MAX;

        return [
            'lab_l'  => $this->clamp($l, 0, ColorSerializer::PERCENT_MAX),
            'lab_a'  => $a,
            'lab_b'  => $b,
            'a'      => ColorSerializer::ALPHA_MAX,
            'format' => ColorFormat::LAB->value,
        ];
    }

    private function parseLabaColor(array $matches): array
    {
        $lRaw     = $matches[1];
        $a        = (float) $matches[2];
        $b        = (float) $matches[3];
        $alphaStr = $matches[4];

        $l = str_contains($lRaw, '%') ? $this->parsePercentageValue($lRaw) : (float) $lRaw * ColorSerializer::PERCENT_MAX;
        $alpha = $this->parseAlpha($alphaStr);

        return [
            'lab_l'  => $this->clamp($l, 0, ColorSerializer::PERCENT_MAX),
            'lab_a'  => $a,
            'lab_b'  => $b,
            'a'      => $this->clamp($alpha, ColorSerializer::ALPHA_MIN, ColorSerializer::ALPHA_MAX),
            'format' => ColorFormat::LABA->value,
        ];
    }

    private function parseLchLikeColor(string $format, array $matches, float $maxChroma): array
    {
        $lRaw = $matches[1];
        $hStr = $matches[3];

        $l = str_contains($lRaw, '%') ? $this->parsePercentageValue($lRaw) : (float) $lRaw * ColorSerializer::PERCENT_MAX;
        $c = (float) $matches[2];
        $h = $this->parseHueValue(trim($hStr));
        $a = isset($matches[4]) ? $this->parseAlpha($matches[4]) : ColorSerializer::ALPHA_MAX;

        $this->validateValueRange($a, ColorSerializer::ALPHA_MIN, ColorSerializer::ALPHA_MAX, 'alpha');

        return [
            'l'      => $this->clamp($l, 0, ColorSerializer::PERCENT_MAX),
            'c'      => $this->clamp($c, 0, $maxChroma),
            'h'      => $this->clamp($h, 0, ColorSerializer::HUE_MAX),
            'a'      => $this->clamp($a, ColorSerializer::ALPHA_MIN, ColorSerializer::ALPHA_MAX),
            'format' => $format,
        ];
    }

    private function parseLchColor(array $matches): array
    {
        return $this->parseLchLikeColor(ColorFormat::LCH->value, $matches, 150);
    }

    private function parseOklchColor(array $matches): array
    {
        return $this->parseLchLikeColor(ColorFormat::OKLCH->value, $matches, 0.5);
    }

    private function parseRgbColor(array $matches): array
    {
        $rgb = $this->parseRgbComponents($matches);
        $a   = isset($matches[4]) ? (float) $matches[4] : ColorSerializer::ALPHA_MAX;

        return [
            'r'      => $rgb['r'],
            'g'      => $rgb['g'],
            'b'      => $rgb['b'],
            'a'      => $this->clamp($a, ColorSerializer::ALPHA_MIN, ColorSerializer::ALPHA_MAX),
            'format' => $a < ColorSerializer::ALPHA_MAX ? ColorFormat::RGBA->value : ColorFormat::RGB->value,
        ];
    }

    private function parseRgbaColor(array $matches): array
    {
        $rgb = $this->parseRgbComponents($matches);
        $a   = (float) $matches[4];

        $this->validateValueRange($a, ColorSerializer::ALPHA_MIN, ColorSerializer::ALPHA_MAX, 'alpha');

        return [
            'r'      => $rgb['r'],
            'g'      => $rgb['g'],
            'b'      => $rgb['b'],
            'a'      => $this->clamp($a, ColorSerializer::ALPHA_MIN, ColorSerializer::ALPHA_MAX),
            'format' => ColorFormat::RGBA->value,
        ];
    }

    private function parseXyzColor(array $matches): array
    {
        $x = (float) $matches[1];
        $y = (float) $matches[2];
        $z = (float) $matches[3];
        $a = isset($matches[4]) ? $this->parseAlpha($matches[4]) : ColorSerializer::ALPHA_MAX;

        $this->validateValueRange($a, ColorSerializer::ALPHA_MIN, ColorSerializer::ALPHA_MAX, 'alpha');

        return [
            'x'      => $this->clamp($x, -1000, 1000),
            'y'      => $this->clamp($y, -1000, 1000),
            'z'      => $this->clamp($z, -1000, 1000),
            'a'      => $this->clamp($a, ColorSerializer::ALPHA_MIN, ColorSerializer::ALPHA_MAX),
            'format' => ColorFormat::XYZ->value,
        ];
    }

    private function parseXyzaColor(array $matches): array
    {
        $x = (float) $matches[1];
        $y = (float) $matches[2];
        $z = (float) $matches[3];
        $a = $this->parseAlpha($matches[4]);

        return [
            'x'      => $this->clamp($x, -1000, 1000),
            'y'      => $this->clamp($y, -1000, 1000),
            'z'      => $this->clamp($z, -1000, 1000),
            'a'      => $this->clamp($a, ColorSerializer::ALPHA_MIN, ColorSerializer::ALPHA_MAX),
            'format' => ColorFormat::XYZA->value,
        ];
    }

    private function applyAdjustments(array $colorData, array $adjustments): array
    {
        $result = ColorSerializer::ensureRgbFormat($colorData);

        $rgbAdjustments = [];
        $hslAdjustments = [];

        foreach ($adjustments as $key => $value) {
            $valueNumber = match (true) {
                isset(self::COLOR_ADJUST_PARAMS[$key]) => (float) $value,
                default => $value,
            };

            match ($key) {
                '$red',
                '$green',
                '$blue'      => $rgbAdjustments[ColorFormat::RGB->value][$this->keyToChannel($key)] = $valueNumber,
                '$hue',
                '$saturation',
                '$lightness' => $hslAdjustments[ColorFormat::HSL->value][$this->keyToHslChannel($key)] = $valueNumber,
                '$alpha'     => $rgbAdjustments['alpha'] = ($rgbAdjustments['alpha'] ?? $result['a']) + $valueNumber,
                '$whiteness' => $rgbAdjustments['whiteness'] = $valueNumber,
                '$blackness' => $rgbAdjustments['blackness'] = $valueNumber,
                '$x'         => $rgbAdjustments['x'] = $valueNumber,
                '$y'         => $rgbAdjustments['y'] = $valueNumber,
                '$z'         => $rgbAdjustments['z'] = $valueNumber,
                '$space'     => $rgbAdjustments['space'] = $valueNumber,
                '$chroma'    => $rgbAdjustments['chroma'] = $valueNumber,
                default      => throw new CompilationException("Unknown adjustment parameter: $key"),
            };
        }

        if (! empty($rgbAdjustments)) {
            $result = $this->applyRgbAdjustments($result, $rgbAdjustments);
        }

        if (! empty($hslAdjustments)) {
            $result = $this->applyHslAdjustments($result, $hslAdjustments);
        }

        return $result;
    }

    private function applyRgbAdjustments(array $colorData, array $adjustments): array
    {
        $result = $colorData;

        foreach ($adjustments as $type => $value) {
            match ($type) {
                ColorFormat::RGB->value => (function () use (&$result, $value): void {
                    foreach ($value as $channel => $adjustment) {
                        $result[$channel] = $this->clamp(
                            $result[$channel] + $adjustment,
                            0,
                            ColorSerializer::RGB_MAX
                        );
                    }
                })(),
                'alpha' => $result['a'] = $this->clamp(
                    $value,
                    ColorSerializer::ALPHA_MIN,
                    ColorSerializer::ALPHA_MAX
                ),
                default => null,
            };
        }

        // Handle HWB adjustments (whiteness/blackness)
        if (isset($adjustments['whiteness']) || isset($adjustments['blackness'])) {
            $hwb = ColorConverter::RGB->toHwb($result['r'], $result['g'], $result['b']);

            $wPercent  = ($hwb['w'] ?? ColorSerializer::ALPHA_MIN) + ($adjustments['whiteness'] ?? ColorSerializer::ALPHA_MIN);
            $blPercent = ($hwb['bl'] ?? ColorSerializer::ALPHA_MIN) + ($adjustments['blackness'] ?? ColorSerializer::ALPHA_MIN);

            $wPercent  = $this->clamp($wPercent, ColorSerializer::ALPHA_MIN, ColorSerializer::PERCENT_MAX);
            $blPercent = $this->clamp($blPercent, ColorSerializer::ALPHA_MIN, ColorSerializer::PERCENT_MAX);

            $rgb    = ColorConverter::HWB->toRgb($hwb['h'] ?? ColorSerializer::ALPHA_MIN, $wPercent, $blPercent);
            $result = array_merge($rgb, ['a' => $result['a'], 'format' => ColorFormat::RGB->value]);
        }

        // Handle chroma adjustments
        if (isset($adjustments['chroma'])) {
            $hsl = ColorConverter::RGB->toHsl($result['r'], $result['g'], $result['b']);
            $currentLightness = $hsl['l'];
            $chromaAdjustment = $adjustments['chroma'];
            $newLightness = $this->clamp(
                $currentLightness + ($chromaAdjustment * 0.5),
                0,
                ColorSerializer::PERCENT_MAX
            );

            // Only adjust if lightness actually changes
            if (abs($newLightness - $currentLightness) > 0.1) {
                $rgb    = ColorConverter::HSL->toRgb($hsl['h'], $hsl['s'], $newLightness);
                $result = array_merge($rgb, ['a' => $result['a'], 'format' => ColorFormat::RGB->value]);
            }
        }

        // Handle XYZ adjustments
        if (
            isset($adjustments['x'])
            || isset($adjustments['y'])
            || isset($adjustments['z'])
            || isset($adjustments['space'])
        ) {
            $xyz = ColorConverter::RGB->toXyz($result['r'], $result['g'], $result['b']);

            $xValue = ($xyz['x'] ?? 0) + ($adjustments['x'] ?? 0);
            $yValue = ($xyz['y'] ?? 0) + ($adjustments['y'] ?? 0);
            $zValue = ($xyz['z'] ?? 0) + ($adjustments['z'] ?? 0);

            // Convert back to RGB
            $rgb = ColorConverter::XYZ->toRgb($xValue, $yValue, $zValue);
            $result = array_merge($rgb, ['a' => $result['a'], 'format' => ColorFormat::RGB->value]);
        }

        return $result;
    }

    private function applyHslAdjustments(array $colorData, array $adjustments): array
    {
        $hsl = ColorConverter::RGB->toHsl($colorData['r'], $colorData['g'], $colorData['b']);

        foreach ($adjustments[ColorFormat::HSL->value] as $channel => $adjustment) {
            match ($channel) {
                'h' => $hsl['h'] = $this->clamp($hsl['h'] + $adjustment, 0, ColorSerializer::HUE_MAX),
                's' => $hsl['s'] = $this->clamp($hsl['s'] + $adjustment, 0, ColorSerializer::PERCENT_MAX),
                'l' => $hsl['l'] = $this->clamp($hsl['l'] + $adjustment, 0, ColorSerializer::PERCENT_MAX),
            };
        }

        $rgb = ColorConverter::HSL->toRgb($hsl['h'], $hsl['s'], $hsl['l']);

        return array_merge($rgb, ['a' => $colorData['a'], 'format' => ColorFormat::RGB->value]);
    }

    private function applyScaling(array $colorData, array $adjustments): array
    {
        $result = ColorSerializer::ensureRgbFormat($colorData);
        $hsl    = ColorConverter::RGB->toHsl($result['r'], $result['g'], $result['b']);

        foreach ($adjustments as $key => $value) {
            $value = (float) $value;
            match ($key) {
                '$red'        => $result['r'] = $this->scaleChannel($result['r'], $value, 0, ColorSerializer::RGB_MAX),
                '$green'      => $result['g'] = $this->scaleChannel($result['g'], $value, 0, ColorSerializer::RGB_MAX),
                '$blue'       => $result['b'] = $this->scaleChannel($result['b'], $value, 0, ColorSerializer::RGB_MAX),
                '$hue'        => $hsl['h'] = $this->scaleChannel($hsl['h'], $value, 0, ColorSerializer::HUE_MAX),
                '$saturation' => $hsl['s'] = $this->scaleChannel($hsl['s'], $value, 0, ColorSerializer::PERCENT_MAX),
                '$lightness'  => $hsl['l'] = $this->scaleChannel($hsl['l'], $value, 0, ColorSerializer::PERCENT_MAX),
                '$alpha'      => $result['a'] = $this->scaleChannel($result['a'], $value, ColorSerializer::ALPHA_MIN, ColorSerializer::ALPHA_MAX),
                default       => throw new CompilationException("Unknown scaling parameter: $key"),
            };
        }

        // If HSL scaling was applied, convert back to RGB
        if (isset($adjustments['$hue']) || isset($adjustments['$saturation']) || isset($adjustments['$lightness'])) {
            $rgb    = ColorConverter::HSL->toRgb($hsl['h'], $hsl['s'], $hsl['l']);
            $result = array_merge($rgb, ['a' => $result['a'], 'format' => ColorFormat::RGB->value]);
        }

        return $result;
    }

    private function applyChanges(array $colorData, array $adjustments): array
    {
        $result = ColorSerializer::ensureRgbFormat($colorData);

        foreach ($adjustments as $key => $value) {
            $value = (float) $value;
            match ($key) {
                '$red'        => $result['r'] = $this->clamp($value, 0, ColorSerializer::RGB_MAX),
                '$green'      => $result['g'] = $this->clamp($value, 0, ColorSerializer::RGB_MAX),
                '$blue'       => $result['b'] = $this->clamp($value, 0, ColorSerializer::RGB_MAX),
                '$alpha'      => $result['a'] = $this->clamp($value, ColorSerializer::ALPHA_MIN, ColorSerializer::ALPHA_MAX),
                '$hue'        => $result = $this->changeHsl($result, 'h', $value),
                '$saturation' => $result = $this->changeHsl($result, 's', $value),
                '$lightness'  => $result = $this->changeHsl($result, 'l', $value),
                default       => throw new CompilationException("Unknown changing parameter: $key"),
            };
        }

        return $result;
    }

    private function changeHsl(array $colorData, string $channel, float $value): array
    {
        $hsl = ColorConverter::RGB->toHsl($colorData['r'], $colorData['g'], $colorData['b']);

        $hsl[$channel] = match ($channel) {
            'h'      => $this->clamp($value, 0, ColorSerializer::HUE_MAX),
            's', 'l' => $this->clamp($value, 0, ColorSerializer::PERCENT_MAX),
        };

        $rgb = ColorConverter::HSL->toRgb($hsl['h'], $hsl['s'], $hsl['l']);

        return array_merge($rgb, ['a' => $colorData['a'], 'format' => ColorFormat::RGB->value]);
    }

    private function scaleChannel(float $current, float $amount, float $min, float $max): float
    {
        $range  = $max - $min;
        $scaled = $current;

        if ($amount > 0) {
            $remaining = $range - ($current - $min);
            $scaled += $remaining * ($amount / ColorSerializer::PERCENT_MAX);
        } elseif ($amount < 0) {
            $progress = $current - $min;
            $scaled -= $progress * (abs($amount) / ColorSerializer::PERCENT_MAX);
        }

        return $this->clamp($scaled, $min, $max);
    }

    private function keyToChannel(string $key): string
    {
        return match ($key) {
            '$red'   => 'r',
            '$green' => 'g',
            '$blue'  => 'b',
            default  => throw new InvalidArgumentException("Invalid RGB key: $key"),
        };
    }

    private function keyToHslChannel(string $key): string
    {
        return match ($key) {
            '$hue'        => 'h',
            '$saturation' => 's',
            '$lightness'  => 'l',
            default       => throw new InvalidArgumentException("Invalid HSL key: $key"),
        };
    }

    private function getColorSpace(string $format): string
    {
        $formatEnum = ColorFormat::tryFrom($format);

        if ($formatEnum === null) {
            return ColorFormat::RGB->value;
        }

        return $formatEnum->getBaseFormat()->value;
    }

    private function clamp(float $value, float $min, float $max): float
    {
        return min(max($value, $min), $max);
    }

    private function parseAlpha(string $alphaStr): float
    {
        if (str_contains($alphaStr, '%')) {
            return (float) rtrim($alphaStr, '%') / ColorSerializer::PERCENT_MAX;
        }

        return (float) $alphaStr;
    }

    private function parseHueValue(string $hueStr): float
    {
        $hue = match (true) {
            str_contains($hueStr, 'rad')  => (float) rtrim($hueStr, 'rad') * ColorSerializer::HUE_SHIFT / M_PI,
            str_contains($hueStr, 'grad') => (float) rtrim($hueStr, 'grad') * ColorSerializer::HUE_MAX / 400,
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

    private function parsePercentageValue(string $percentStr): float
    {
        return (float) rtrim($percentStr, '%');
    }

    private function validateValueRange(float $value, float $min, float $max, string $name): void
    {
        if ($value < $min || $value > $max) {
            throw new CompilationException("Invalid $name value: $value");
        }
    }

    private function getAllValidChannels(): array
    {
        static $validChannels = null;

        if ($validChannels === null) {
            $validChannels = [];
            foreach (ColorFormat::cases() as $format) {
                $validChannels = array_merge($validChannels, $format->getChannels());
            }

            $validChannels = array_unique($validChannels);
        }

        return $validChannels;
    }

    private function validateChannel(string $channel): void
    {
        $validChannels = $this->getAllValidChannels();

        if (! in_array($channel, $validChannels, true)) {
            throw new CompilationException("Unknown channel: $channel");
        }
    }

    private function channelToDataKey(string $channel): string
    {
        if (strlen($channel) === 1) {
            return $channel;
        }

        return match ($channel) {
            'red'        => 'r',
            'green'      => 'g',
            'blue'       => 'b',
            'hue'        => 'h',
            'saturation' => 's',
            'lightness'  => 'l',
            'whiteness'  => 'w',
            'blackness'  => 'bl',
            'alpha'      => 'a',
            'lab_l'      => 'lab_l',
            'lab_a'      => 'lab_a',
            'lab_b'      => 'lab_b',
            'chroma'     => 'c',
            default      => $channel,
        };
    }

    private function convertToSpace(array $colorData, string $targetSpace): array
    {
        $originalFormat = $colorData['format'] ?? ColorFormat::RGB->value;
        $targetFormat   = ColorFormat::tryFrom($targetSpace);
        $currentFormat  = ColorFormat::tryFrom($originalFormat);

        if ($currentFormat && $targetFormat && $currentFormat->isCompatibleWith($targetFormat)) {
            return $colorData;
        }

        $converted = match ($targetSpace) {
            ColorFormat::HSL->value,
            ColorFormat::HSLA->value  => ColorConverter::RGB->toHsl($colorData['r'], $colorData['g'], $colorData['b']),
            ColorFormat::HWB->value   => ColorConverter::RGB->toHwb($colorData['r'], $colorData['g'], $colorData['b']),
            ColorFormat::LAB->value,
            ColorFormat::LABA->value  => ColorConverter::RGB->toLab($colorData['r'], $colorData['g'], $colorData['b']),
            ColorFormat::LCH->value   => ColorConverter::RGB->toLch($colorData['r'], $colorData['g'], $colorData['b']),
            ColorFormat::OKLCH->value => ColorConverter::RGB->toOklch($colorData['r'], $colorData['g'], $colorData['b']),
            ColorFormat::XYZ->value,
            ColorFormat::XYZA->value  => ColorConverter::RGB->toXyz($colorData['r'], $colorData['g'], $colorData['b']),
            default                   => ColorSerializer::ensureRgbFormat($colorData),
        };

        if ($targetSpace !== ColorFormat::RGB->value) {
            $converted['format'] = $targetSpace;
        }

        return array_merge($converted, ['a' => $colorData['a'] ?? ColorSerializer::ALPHA_MAX]);
    }
}
