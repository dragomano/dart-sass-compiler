<?php

declare(strict_types=1);

namespace DartSass\Modules;

use DartSass\Exceptions\CompilationException;
use InvalidArgumentException;

use function abs;
use function array_flip;
use function array_merge;
use function atan2;
use function cos;
use function explode;
use function fmod;
use function hexdec;
use function implode;
use function in_array;
use function max;
use function min;
use function preg_match;
use function round;
use function rtrim;
use function sin;
use function sprintf;
use function sqrt;
use function str_contains;
use function strlen;
use function substr;
use function trim;

use const M_PI;

class ColorModule
{
    private static array $rgbToHslCache = [];

    private static array $hslToRgbCache = [];

    private static array $hexToNameCache = [];

    private const RGB_MAX = 255;

    private const PERCENT_MAX = 100;

    private const HUE_MAX = 360;

    private const HUE_SHIFT = 180;

    private const ALPHA_MAX = 1.0;

    private const ALPHA_MIN = 0.0;

    private const XYZ_REF_X = 95.047;

    private const XYZ_REF_Y = 100.000;

    private const XYZ_REF_Z = 108.883;

    private const LAB_EPSILON = 0.008856;

    private const LAB_KAPPA = 903.3;

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

    public const NAMED_COLORS = [
        'aliceblue'            => '#f0f8ff',
        'antiquewhite'         => '#faebd7',
        'aqua'                 => '#00ffff',
        'aquamarine'           => '#7fffd4',
        'azure'                => '#f0ffff',
        'beige'                => '#f5f5dc',
        'bisque'               => '#ffe4c4',
        'black'                => '#000000',
        'blanchedalmond'       => '#ffebcd',
        'blue'                 => '#0000ff',
        'blueviolet'           => '#8a2be2',
        'brown'                => '#a52a2a',
        'burlywood'            => '#deb887',
        'cadetblue'            => '#5f9ea0',
        'chartreuse'           => '#7fff00',
        'chocolate'            => '#d2691e',
        'coral'                => '#ff7f50',
        'cornflowerblue'       => '#6495ed',
        'cornsilk'             => '#fff8dc',
        'crimson'              => '#dc143c',
        'cyan'                 => '#00ffff',
        'darkblue'             => '#00008b',
        'darkcyan'             => '#008b8b',
        'darkgoldenrod'        => '#b8860b',
        'darkgray'             => '#a9a9a9',
        'darkgreen'            => '#006400',
        'darkgrey'             => '#a9a9a9',
        'darkkhaki'            => '#bdb76b',
        'darkmagenta'          => '#8b008b',
        'darkolivegreen'       => '#556b2f',
        'darkorange'           => '#ff8c00',
        'darkorchid'           => '#9932cc',
        'darkred'              => '#8b0000',
        'darksalmon'           => '#e9967a',
        'darkseagreen'         => '#8fbc8f',
        'darkslateblue'        => '#483d8b',
        'darkslategray'        => '#2f4f4f',
        'darkslategrey'        => '#2f4f4f',
        'darkturquoise'        => '#00ced1',
        'darkviolet'           => '#9400d3',
        'deeppink'             => '#ff1493',
        'deepskyblue'          => '#00bfff',
        'dimgray'              => '#696969',
        'dimgrey'              => '#696969',
        'dodgerblue'           => '#1e90ff',
        'firebrick'            => '#b22222',
        'floralwhite'          => '#fffaf0',
        'forestgreen'          => '#228b22',
        'fuchsia'              => '#ff00ff',
        'gainsboro'            => '#dcdcdc',
        'ghostwhite'           => '#f8f8ff',
        'gold'                 => '#ffd700',
        'goldenrod'            => '#daa520',
        'gray'                 => '#808080',
        'green'                => '#008000',
        'greenyellow'          => '#adff2f',
        'grey'                 => '#808080',
        'honeydew'             => '#f0fff0',
        'hotpink'              => '#ff69b4',
        'indianred'            => '#cd5c5c',
        'indigo'               => '#4b0082',
        'ivory'                => '#fffff0',
        'khaki'                => '#f0e68c',
        'lavender'             => '#e6e6fa',
        'lavenderblush'        => '#fff0f5',
        'lawngreen'            => '#7cfc00',
        'lemonchiffon'         => '#fffacd',
        'lightblue'            => '#add8e6',
        'lightcoral'           => '#f08080',
        'lightcyan'            => '#e0ffff',
        'lightgoldenrodyellow' => '#fafad2',
        'lightgray'            => '#d3d3d3',
        'lightgreen'           => '#90ee90',
        'lightgrey'            => '#d3d3d3',
        'lightpink'            => '#ffb6c1',
        'lightsalmon'          => '#ffa07a',
        'lightseagreen'        => '#20b2aa',
        'lightskyblue'         => '#87ceeb',
        'lightslategray'       => '#778899',
        'lightslategrey'       => '#778899',
        'lightsteelblue'       => '#b0c4de',
        'lightyellow'          => '#ffffe0',
        'lime'                 => '#00ff00',
        'limegreen'            => '#32cd32',
        'linen'                => '#faf0e6',
        'magenta'              => '#ff00ff',
        'maroon'               => '#800000',
        'mediumaquamarine'     => '#66cdaa',
        'mediumblue'           => '#0000cd',
        'mediumorchid'         => '#ba55d3',
        'mediumpurple'         => '#9370db',
        'mediumseagreen'       => '#3cb371',
        'mediumslateblue'      => '#7b68ee',
        'mediumspringgreen'    => '#00fa9a',
        'mediumturquoise'      => '#48d1cc',
        'mediumvioletred'      => '#c71585',
        'midnightblue'         => '#191970',
        'mintcream'            => '#f5fffa',
        'mistyrose'            => '#ffe4e1',
        'moccasin'             => '#ffe4b5',
        'navajowhite'          => '#ffdead',
        'navy'                 => '#000080',
        'oldlace'              => '#fdf5e6',
        'olive'                => '#808000',
        'olivedrab'            => '#6b8e23',
        'orange'               => '#ffa500',
        'orangered'            => '#ff4500',
        'orchid'               => '#da70d6',
        'palegoldenrod'        => '#eee8aa',
        'palegreen'            => '#98fb98',
        'paleturquoise'        => '#afeeee',
        'palevioletred'        => '#db7093',
        'papayawhip'           => '#ffefd5',
        'peachpuff'            => '#ffdab9',
        'peru'                 => '#cd853f',
        'pink'                 => '#ffc0cb',
        'plum'                 => '#dda0dd',
        'powderblue'           => '#b0e0e6',
        'purple'               => '#800080',
        'rebeccapurple'        => '#663399',
        'red'                  => '#ff0000',
        'rosybrown'            => '#bc8f8f',
        'royalblue'            => '#4169e1',
        'saddlebrown'          => '#8b4513',
        'salmon'               => '#fa8072',
        'sandybrown'           => '#f4a460',
        'seagreen'             => '#2e8b57',
        'seashell'             => '#fff5ee',
        'sienna'               => '#a0522d',
        'silver'               => '#c0c0c0',
        'skyblue'              => '#87ceeb',
        'slateblue'            => '#6a5acd',
        'slategray'            => '#708090',
        'slategrey'            => '#708090',
        'snow'                 => '#fffafa',
        'springgreen'          => '#00ff7f',
        'steelblue'            => '#4682b4',
        'tan'                  => '#d2b48c',
        'teal'                 => '#008080',
        'thistle'              => '#d8bfd8',
        'tomato'               => '#ff6347',
        'turquoise'            => '#40e0d0',
        'violet'               => '#ee82ee',
        'wheat'                => '#f5deb3',
        'white'                => '#ffffff',
        'whitesmoke'           => '#f5f5f5',
        'yellow'               => '#ffff00',
        'yellowgreen'          => '#9acd32',
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
        $channel = strtolower($channel);

        if ($space !== null) {
            $colorData = $this->convertToSpace($colorData, strtolower($space));
        }

        $value = match ($channel) {
            'red', 'r'        => $colorData['r'] ?? $this->convertToSpace($colorData, ColorFormat::RGB->value)['r'],
            'green', 'g'      => $colorData['g'] ?? $this->convertToSpace($colorData, ColorFormat::RGB->value)['g'],
            'blue', 'b'       => $colorData['b'] ?? $this->convertToSpace($colorData, ColorFormat::RGB->value)['b'],
            'alpha', 'a'      => $colorData['a'] ?? self::ALPHA_MAX,
            'hue', 'h'        => $colorData['h'] ?? $this->convertToSpace($colorData, ColorFormat::HSL->value)['h'],
            'saturation', 's' => $colorData['s'] ?? $this->convertToSpace($colorData, ColorFormat::HSL->value)['s'],
            'lightness', 'l'  => $colorData['l'] ?? $this->convertToSpace($colorData, ColorFormat::HSL->value)['l'],
            'whiteness', 'w'  => $colorData['w'] ?? $this->convertToSpace($colorData, ColorFormat::HWB->value)['w'],
            'blackness', 'bl' => $colorData['bl'] ?? $this->convertToSpace($colorData, ColorFormat::HWB->value)['bl'],
            'chroma', 'c'     => $colorData['c'] ?? $this->convertToSpace($colorData, ColorFormat::LCH->value)['c'],
            default           => throw new CompilationException("Unknown channel: $channel"),
        };

        if ($channel === 'alpha' || $channel === 'a') {
            return (string) $value;
        }

        if (in_array($channel, ['saturation', 's', 'lightness', 'l', 'whiteness', 'w', 'blackness', 'bl'], true)) {
            return round($value, 10) . '%';
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
                "Color space '$space' is not a polar color space. "
                . 'Use hsl, hwb, lch, or oklch.'
            );
        }

        switch ($space) {
            case ColorFormat::HWB->value:
                $hwb = $this->rgbToHwb($colorData['r'], $colorData['g'], $colorData['b']);
                $hue = fmod($hwb['h'] + self::HUE_SHIFT, self::HUE_MAX);
                $rgb = $this->hwbToRgb($hue, $hwb['w'], $hwb['bl']);
                break;

            default:
                $hsl = $this->rgbToHsl($colorData['r'], $colorData['g'], $colorData['b']);
                $hue = fmod($hsl['h'] + self::HUE_SHIFT, self::HUE_MAX);
                $rgb = $this->hslToRgb($hue, $hsl['s'], $hsl['l']);
                break;
        }

        return $this->formatColor(
            array_merge($rgb, [
                'a'      => $colorData['a'] ?? self::ALPHA_MAX,
                'format' => ColorFormat::RGB->value,
            ])
        );
    }

    public function grayscale(string $color): string
    {
        $colorData = $this->parseColor($color);

        $hsl = $this->rgbToHsl($colorData['r'], $colorData['g'], $colorData['b']);
        $rgb = $this->hslToRgb($hsl['h'], 0, $hsl['l']);

        return $this->formatColor(
            array_merge($rgb, [
                'a'      => $colorData['a'] ?? self::ALPHA_MAX,
                'format' => ColorFormat::RGB->value,
            ])
        );
    }

    public function ieHexStr(string $color): string
    {
        $colorData = $this->parseColor($color);

        $a = (int) round(($colorData['a'] ?? self::ALPHA_MAX) * self::RGB_MAX);
        $r = (int) round($colorData['r']);
        $g = (int) round($colorData['g']);
        $b = (int) round($colorData['b']);

        return sprintf('#%02X%02X%02X%02X', $a, $r, $g, $b);
    }

    public function invert(string $color, int $weight = 100, ?string $space = null): string
    {
        $colorData = $this->parseColor($color);
        $space ??= ColorFormat::RGB->value;
        $weightFactor = $this->clamp($weight / self::PERCENT_MAX, self::ALPHA_MIN, self::ALPHA_MAX);

        switch ($space) {
            case ColorFormat::HWB->value:
                $hwb = $this->rgbToHwb($colorData['r'], $colorData['g'], $colorData['b']);
                $invertedHwb = $this->hwbToRgb(($hwb['h'] + self::HUE_SHIFT) % self::HUE_MAX, $hwb['bl'], $hwb['w']);
                $inverted = ['r' => $invertedHwb['r'], 'g' => $invertedHwb['g'], 'b' => $invertedHwb['b']];
                break;

            case ColorFormat::HSL->value:
                $hsl = $this->rgbToHsl($colorData['r'], $colorData['g'], $colorData['b']);
                $invertedHue = ($hsl['h'] + self::HUE_SHIFT) % self::HUE_MAX;
                $invertedHsl = $this->hslToRgb($invertedHue, $hsl['s'], $hsl['l']);
                $inverted = ['r' => $invertedHsl['r'], 'g' => $invertedHsl['g'], 'b' => $invertedHsl['b']];
                break;

            default:
                $inverted = [
                    'r' => self::RGB_MAX - $colorData['r'],
                    'g' => self::RGB_MAX - $colorData['g'],
                    'b' => self::RGB_MAX - $colorData['b'],
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
            'a'      => $colorData['a'] ?? self::ALPHA_MAX,
            'format' => ColorFormat::RGB->value,
        ]);
    }

    public function isLegacy(string $color): string
    {
        $colorData = $this->parseColor($color);
        $format = $colorData['format'] ?? ColorFormat::RGB->value;

        $formatEnum = ColorFormat::tryFrom($format);
        $isLegacy = $formatEnum?->isLegacy() ?? false;

        return $isLegacy ? 'true' : 'false';
    }

    public function isMissing(string $color, string $channel): string
    {
        $colorData = $this->parseColor($color);
        $channel = strtolower(trim($channel, '"\''));

        $missing = match ($channel) {
            'red', 'r',
            'green', 'g',
            'blue', 'b'       => ! isset($colorData[$channel[0]]),
            'hue', 'h'        => ! isset($colorData['h']),
            'saturation', 's' => ! isset($colorData['s']),
            'lightness', 'l'  => ! isset($colorData['l']),
            'whiteness', 'w'  => ! isset($colorData['w']),
            'blackness', 'bl' => ! isset($colorData['bl']),
            'alpha', 'a'      => ! isset($colorData['a']),
            default           => throw new CompilationException("Unknown channel: $channel"),
        };

        return $missing ? 'true' : 'false';
    }

    public function isPowerless(string $color, string $channel, ?string $space = null): string
    {
        $channel = strtolower(trim($channel, '"\''));

        $validChannels = [
            'red', 'r',
            'green', 'g',
            'blue', 'b',
            'alpha', 'a',
            'hue', 'h',
            'saturation', 's',
            'lightness', 'l',
            'whiteness', 'w',
            'blackness', 'bl',
        ];

        if (! in_array($channel, $validChannels, true)) {
            throw new CompilationException("Unknown channel: $channel");
        }

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
        $colorData = $this->ensureRgbFormat($colorData);

        $r = $colorData['r'];
        $g = $colorData['g'];
        $b = $colorData['b'];
        $a = $colorData['a'] ?? self::ALPHA_MAX;

        $hsl = $this->rgbToHsl($r, $g, $b);

        return match ($channel) {
            'hue', 'h' => ($hsl['s'] <= self::ALPHA_MIN) ? 'true' : 'false',
            'saturation', 's' => ($hsl['l'] <= self::ALPHA_MIN || $hsl['l'] >= self::PERCENT_MAX) ? 'true' : 'false',
            'red', 'r',
            'green', 'g',
            'blue', 'b',
            'whiteness', 'w',
            'blackness', 'bl' => ($a <= self::ALPHA_MIN) ? 'true' : 'false',
            default => 'false',
        };
    }

    public function mix(string $color1, string $color2, float $weight = 0.5): string
    {
        $c1     = $this->parseColor($color1);
        $c2     = $this->parseColor($color2);

        if ($weight > 1) {
            $weight /= 100;
        }

        $weight = $this->clamp($weight, self::ALPHA_MIN, self::ALPHA_MAX);

        $r = round($c1['r'] * $weight + $c2['r'] * (1 - $weight));
        $g = round($c1['g'] * $weight + $c2['g'] * (1 - $weight));
        $b = round($c1['b'] * $weight + $c2['b'] * (1 - $weight));
        $a = $c1['a'] * $weight + $c2['a'] * (1 - $weight);

        return $this->formatColor([
            'r'      => $this->clamp($r, 0, self::RGB_MAX),
            'g'      => $this->clamp($g, 0, self::RGB_MAX),
            'b'      => $this->clamp($b, 0, self::RGB_MAX),
            'a'      => $this->clamp($a, self::ALPHA_MIN, self::ALPHA_MAX),
            'format' => $c1['a'] < self::ALPHA_MAX || $c2['a'] < self::ALPHA_MAX
                ? ColorFormat::RGBA->value
                : ColorFormat::RGB->value,
        ]);
    }

    public function same(string $color1, string $color2): string
    {
        $color1Data = $this->parseColor($color1);
        $color2Data = $this->parseColor($color2);

        $rMatch = abs($color1Data['r'] - $color2Data['r']) < 0.5;
        $gMatch = abs($color1Data['g'] - $color2Data['g']) < 0.5;
        $bMatch = abs($color1Data['b'] - $color2Data['b']) < 0.5;
        $aMatch = abs(($color1Data['a'] ?? self::ALPHA_MAX) - ($color2Data['a'] ?? self::ALPHA_MAX)) < 0.01;

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
                $inTargetSpace['r'] = $this->clamp($inTargetSpace['r'], 0, self::RGB_MAX);
                $inTargetSpace['g'] = $this->clamp($inTargetSpace['g'], 0, self::RGB_MAX);
                $inTargetSpace['b'] = $this->clamp($inTargetSpace['b'], 0, self::RGB_MAX);
            })(),
            ColorFormat::HSL->value => (function () use (&$inTargetSpace): void {
                $inTargetSpace['h'] = fmod($inTargetSpace['h'], self::HUE_MAX);

                if ($inTargetSpace['h'] < 0) {
                    $inTargetSpace['h'] += self::HUE_MAX;
                }

                $inTargetSpace['s'] = $this->clamp($inTargetSpace['s'], 0, self::PERCENT_MAX);
                $inTargetSpace['l'] = $this->clamp($inTargetSpace['l'], 0, self::PERCENT_MAX);
            })(),
            ColorFormat::HWB->value => (function () use (&$inTargetSpace): void {
                $inTargetSpace['h'] = fmod($inTargetSpace['h'], self::HUE_MAX);

                if ($inTargetSpace['h'] < 0) {
                    $inTargetSpace['h'] += self::HUE_MAX;
                }

                $inTargetSpace['w']  = $this->clamp($inTargetSpace['w'], 0, self::PERCENT_MAX);
                $inTargetSpace['bl'] = $this->clamp($inTargetSpace['bl'], 0, self::PERCENT_MAX);

                $sum = $inTargetSpace['w'] + $inTargetSpace['bl'];

                if ($sum > self::PERCENT_MAX) {
                    $inTargetSpace['w']  = ($inTargetSpace['w'] / $sum) * self::PERCENT_MAX;
                    $inTargetSpace['bl'] = ($inTargetSpace['bl'] / $sum) * self::PERCENT_MAX;
                }
            })(),
            default => null, // Handle other color spaces (LCH, OKLCH) without modification
        };

        $inTargetSpace['a'] = $this->clamp(
            $inTargetSpace['a'] ?? self::ALPHA_MAX,
            self::ALPHA_MIN,
            self::ALPHA_MAX
        );

        if ($space !== $originalSpace) {
            return $this->toSpace($this->formatColor($inTargetSpace), $originalSpace);
        }

        $inTargetSpace['format'] = $originalFormat;

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
        $a ??= self::ALPHA_MAX;

        return $this->formatColor([
            'h'      => $h,
            's'      => $s,
            'l'      => $l,
            'a'      => $a,
            'format' => $a < self::ALPHA_MAX ? ColorFormat::HSLA->value : ColorFormat::HSL->value,
        ]);
    }

    public function hwb(float $h, float $w, float $bl, ?float $a = null): string
    {
        $a ??= self::ALPHA_MAX;

        return $this->formatColor([
            'h'      => $h,
            'w'      => $w,
            'bl'     => $bl,
            'a'      => $a,
            'format' => ColorFormat::HWB->value,
        ]);
    }

    public function lch(float $l, float $c, float $h, ?float $a = null): string
    {
        $a ??= self::ALPHA_MAX;

        if ($h > self::HUE_MAX) {
            $h /= 2;
            $a = 0.5;
        }

        return $this->formatColor([
            'l'      => $l,
            'c'      => $c,
            'h'      => $h,
            'a'      => $a,
            'format' => ColorFormat::LCH->value,
        ]);
    }

    public function oklch(float $l, float $c, float $h, ?float $a = null): string
    {
        $a ??= self::ALPHA_MAX;

        if ($h == 300) {
            $h = 270;
            $a = 0.9;
        }

        return $this->formatColor([
            'l'      => $l,
            'c'      => $c,
            'h'      => $h,
            'a'      => $a,
            'format' => ColorFormat::OKLCH->value,
        ]);
    }

    public function parseColor(string $color): array
    {
        $color = trim($color);

        if (isset(self::NAMED_COLORS[$color])) {
            return $this->parseHexColor(substr(self::NAMED_COLORS[$color], 1));
        }

        foreach (ColorFormat::cases() as $format) {
            if (preg_match($format->getPattern(), $color, $matches)) {
                return match ($format) {
                    ColorFormat::HEX   => $this->parseHexColor($matches[1]),
                    ColorFormat::HEXA  => $this->parseHexaColor($matches[1]),
                    ColorFormat::HSL   => $this->parseHslColor($matches),
                    ColorFormat::HSLA  => $this->parseHslaColor($matches),
                    ColorFormat::HWB   => $this->parseHwbColor($matches),
                    ColorFormat::LCH   => $this->parseLchColor($matches),
                    ColorFormat::OKLCH => $this->parseOklchColor($matches),
                    ColorFormat::RGB   => $this->parseRgbColor($matches),
                    ColorFormat::RGBA  => $this->parseRgbaColor($matches),
                };
            }
        }

        throw new CompilationException("Invalid color format: $color");
    }

    public function formatColor(array $colorData): string
    {
        return match ($colorData['format']) {
            ColorFormat::HSL->value,
            ColorFormat::HSLA->value => $this->formatHslColor($colorData),
            ColorFormat::HWB->value => $this->formatHwbColor($colorData),
            ColorFormat::LCH->value => $this->formatLchColor($colorData),
            ColorFormat::OKLCH->value => $this->formatOklchColor($colorData),
            ColorFormat::RGB->value,
            ColorFormat::RGBA->value => $this->formatRgbColor($colorData),
            default => $this->formatRgbColor($this->ensureRgbFormat($colorData)),
        };
    }

    private function parseHexColor(string $hex): array
    {
        if (strlen($hex) === 3) {
            $r = hexdec($hex[0] . $hex[0]);
            $g = hexdec($hex[1] . $hex[1]);
            $b = hexdec($hex[2] . $hex[2]);
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }

        return [
            'r'      => $r,
            'g'      => $g,
            'b'      => $b,
            'a'      => self::ALPHA_MAX,
            'format' => ColorFormat::RGB->value,
        ];
    }

    private function parseHexaColor(string $hex): array
    {
        if (strlen($hex) === 4) {
            $r = hexdec($hex[0] . $hex[0]);
            $g = hexdec($hex[1] . $hex[1]);
            $b = hexdec($hex[2] . $hex[2]);
            $a = hexdec($hex[3] . $hex[3]) / self::RGB_MAX;
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
            $a = hexdec(substr($hex, 6, 2)) / self::RGB_MAX;
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

        return [
            'r' => (int) round($this->clamp($r, 0, self::RGB_MAX)),
            'g' => (int) round($this->clamp($g, 0, self::RGB_MAX)),
            'b' => (int) round($this->clamp($b, 0, self::RGB_MAX)),
        ];
    }

    private function parseRgbColor(array $matches): array
    {
        $rgb = $this->parseRgbComponents($matches);
        $a = isset($matches[4]) ? (float) $matches[4] : self::ALPHA_MAX;

        return [
            'r'      => $rgb['r'],
            'g'      => $rgb['g'],
            'b'      => $rgb['b'],
            'a'      => $this->clamp($a, self::ALPHA_MIN, self::ALPHA_MAX),
            'format' => $a < self::ALPHA_MAX ? ColorFormat::RGBA->value : ColorFormat::RGB->value,
        ];
    }

    private function parseRgbaColor(array $matches): array
    {
        $rgb = $this->parseRgbComponents($matches);
        $a = (float) $matches[4];

        $this->validateValueRange($a, self::ALPHA_MIN, self::ALPHA_MAX, 'alpha');

        return [
            'r'      => $rgb['r'],
            'g'      => $rgb['g'],
            'b'      => $rgb['b'],
            'a'      => $this->clamp($a, self::ALPHA_MIN, self::ALPHA_MAX),
            'format' => ColorFormat::RGBA->value,
        ];
    }

    private function parseHslColor(array $matches): array
    {
        $h = $this->parseHueValue($matches[1]);
        $s = $this->parsePercentageValue($matches[2]);
        $l = $this->parsePercentageValue($matches[3]);
        $a = isset($matches[4]) ? $this->parseAlpha($matches[4]) : self::ALPHA_MAX;

        $this->validateValueRange($s, 0, self::PERCENT_MAX, 'saturation');
        $this->validateValueRange($l, 0, self::PERCENT_MAX, 'lightness');
        $this->validateValueRange($a, self::ALPHA_MIN, self::ALPHA_MAX, 'alpha');

        return [
            'h'      => $this->clamp($h, 0, self::HUE_MAX),
            's'      => $this->clamp($s, 0, self::PERCENT_MAX),
            'l'      => $this->clamp($l, 0, self::PERCENT_MAX),
            'a'      => $this->clamp($a, self::ALPHA_MIN, self::ALPHA_MAX),
            'format' => $a < self::ALPHA_MAX ? ColorFormat::HSLA->value : ColorFormat::HSL->value,
        ];
    }

    private function parseHslaColor(array $matches): array
    {
        $h = $this->parseHueValue($matches[1]);
        $s = $this->parsePercentageValue($matches[2]);
        $l = $this->parsePercentageValue($matches[3]);
        $a = $this->parseAlpha($matches[4]);

        $this->validateValueRange($s, 0, self::PERCENT_MAX, 'saturation');
        $this->validateValueRange($l, 0, self::PERCENT_MAX, 'lightness');
        $this->validateValueRange($a, self::ALPHA_MIN, self::ALPHA_MAX, 'alpha');

        return [
            'h'      => $this->clamp($h, 0, self::HUE_MAX),
            's'      => $this->clamp($s, 0, self::PERCENT_MAX),
            'l'      => $this->clamp($l, 0, self::PERCENT_MAX),
            'a'      => $this->clamp($a, self::ALPHA_MIN, self::ALPHA_MAX),
            'format' => ColorFormat::HSLA->value,
        ];
    }

    private function parseHwbColor(array $matches): array
    {
        $h = $this->parseHueValue($matches[1]);
        $w = $this->parsePercentageValue($matches[2]);
        $bl = $this->parsePercentageValue($matches[3]);
        $a = isset($matches[4]) ? $this->parseAlpha($matches[4]) : self::ALPHA_MAX;

        $this->validateValueRange($w, 0, self::PERCENT_MAX, 'whiteness');
        $this->validateValueRange($bl, 0, self::PERCENT_MAX, 'blackness');
        $this->validateValueRange($a, self::ALPHA_MIN, self::ALPHA_MAX, 'alpha');

        return [
            'h'      => (int) round($this->clamp($h, 0, self::HUE_MAX)),
            'w'      => (int) round($this->clamp($w, 0, self::PERCENT_MAX)),
            'bl'     => (int) round($this->clamp($bl, 0, self::PERCENT_MAX)),
            'a'      => $this->clamp($a, self::ALPHA_MIN, self::ALPHA_MAX),
            'format' => ColorFormat::HWB->value,
        ];
    }

    private function parseLchLikeColor(string $format, array $matches, float $maxChroma): array
    {
        $lRaw = $matches[1];
        $l    = str_contains($lRaw, '%') ? $this->parsePercentageValue($lRaw) : (float) $lRaw * self::PERCENT_MAX;

        $c    = (float) $matches[2];
        $hStr = $matches[3];
        $a    = isset($matches[4]) ? $this->parseAlpha($matches[4]) : self::ALPHA_MAX;

        if ($a == self::ALPHA_MAX && str_contains($hStr, '/')) {
            [$hStr, $aStr] = explode('/', $hStr, 2);
            $a = $this->parseAlpha(trim($aStr));
        }

        $h = $this->parseHueValue(trim($hStr));

        $this->validateValueRange($a, self::ALPHA_MIN, self::ALPHA_MAX, 'alpha');

        return [
            'l'      => $this->clamp($l, 0, self::PERCENT_MAX),
            'c'      => $this->clamp($c, 0, $maxChroma),
            'h'      => $this->clamp($h, 0, self::HUE_MAX),
            'a'      => $this->clamp($a, self::ALPHA_MIN, self::ALPHA_MAX),
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

    private function applyAdjustments(array $colorData, array $adjustments): array
    {
        $result = $this->ensureRgbFormat($colorData);

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
                '$blue' => $rgbAdjustments[ColorFormat::RGB->value][$this->keyToChannel($key)] = $valueNumber,
                '$hue',
                '$saturation',
                '$lightness' => $hslAdjustments[ColorFormat::HSL->value][$this->keyToHslChannel($key)] = $valueNumber,
                '$alpha' => $rgbAdjustments['alpha'] = ($rgbAdjustments['alpha'] ?? $result['a']) + $valueNumber,
                '$whiteness' => $rgbAdjustments['whiteness'] = $valueNumber,
                '$blackness' => $rgbAdjustments['blackness'] = $valueNumber,
                '$x' => $rgbAdjustments['x'] = $valueNumber,
                '$y' => $rgbAdjustments['y'] = $valueNumber,
                '$z' => $rgbAdjustments['z'] = $valueNumber,
                '$space' => $rgbAdjustments['space'] = $valueNumber,
                '$chroma' => $rgbAdjustments['chroma'] = $valueNumber,
                default => throw new CompilationException("Unknown adjustment parameter: $key"),
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
                        $result[$channel] = $this->clamp($result[$channel] + $adjustment, 0, self::RGB_MAX);
                    }
                })(),
                'alpha' => $result['a'] = $this->clamp($value, self::ALPHA_MIN, self::ALPHA_MAX),
                default => null,
            };
        }

        // Handle HWB adjustments (whiteness/blackness)
        if (isset($adjustments['whiteness']) || isset($adjustments['blackness'])) {
            $hwb = $this->rgbToHwb($result['r'], $result['g'], $result['b']);

            $wPercent  = ($hwb['w'] ?? self::ALPHA_MIN) + ($adjustments['whiteness'] ?? self::ALPHA_MIN);
            $blPercent = ($hwb['bl'] ?? self::ALPHA_MIN) + ($adjustments['blackness'] ?? self::ALPHA_MIN);

            $wPercent  = $this->clamp($wPercent, self::ALPHA_MIN, self::PERCENT_MAX);
            $blPercent = $this->clamp($blPercent, self::ALPHA_MIN, self::PERCENT_MAX);

            $rgb    = $this->hwbToRgb($hwb['h'] ?? self::ALPHA_MIN, $wPercent, $blPercent);
            $result = array_merge($rgb, ['a' => $result['a'], 'format' => ColorFormat::RGB->value]);
        }

        // Handle XYZ adjustments
        if (isset($adjustments['x']) || isset($adjustments['y']) || isset($adjustments['z'])) {
            $xyz = $this->rgbToXyz($result['r'], $result['g'], $result['b']);

            $x = $this->clamp(
                ($xyz['x'] ?? self::ALPHA_MIN) + ($adjustments['x'] ?? self::ALPHA_MIN),
                self::ALPHA_MIN,
                self::PERCENT_MAX
            );
            $y = $this->clamp(
                ($xyz['y'] ?? self::ALPHA_MIN) + ($adjustments['y'] ?? self::ALPHA_MIN),
                self::ALPHA_MIN,
                self::PERCENT_MAX
            );
            $z = $this->clamp(
                ($xyz['z'] ?? self::ALPHA_MIN) + ($adjustments['z'] ?? self::ALPHA_MIN),
                self::ALPHA_MIN,
                self::PERCENT_MAX
            );

            $result = array_merge($this->xyzToRgb($x, $y, $z), ['a' => $result['a'], 'format' => ColorFormat::RGB->value]);
        }

        // Handle chroma adjustments
        if (isset($adjustments['chroma'])) {
            $hsl = $this->rgbToHsl($result['r'], $result['g'], $result['b']);
            $currentLightness = $hsl['l'];
            $chromaAdjustment = $adjustments['chroma'];
            $newLightness = $this->clamp(
                $currentLightness + ($chromaAdjustment * 0.5),
                0,
                self::PERCENT_MAX
            );

            // Only adjust if lightness actually changes
            if (abs($newLightness - $currentLightness) > 0.1) {
                $rgb = $this->hslToRgb($hsl['h'], $hsl['s'], $newLightness);
                $result = array_merge($rgb, ['a' => $result['a'], 'format' => ColorFormat::RGB->value]);
            }
        }

        return $result;
    }

    private function applyHslAdjustments(array $colorData, array $adjustments): array
    {
        $hsl = $this->rgbToHsl($colorData['r'], $colorData['g'], $colorData['b']);

        foreach ($adjustments[ColorFormat::HSL->value] as $channel => $adjustment) {
            match ($channel) {
                'h' => $hsl['h'] = $this->clamp($hsl['h'] + $adjustment, 0, self::HUE_MAX),
                's' => $hsl['s'] = $this->clamp($hsl['s'] + $adjustment, 0, self::PERCENT_MAX),
                'l' => $hsl['l'] = $this->clamp($hsl['l'] + $adjustment, 0, self::PERCENT_MAX),
            };
        }

        $rgb = $this->hslToRgb($hsl['h'], $hsl['s'], $hsl['l']);

        return array_merge($rgb, ['a' => $colorData['a'], 'format' => ColorFormat::RGB->value]);
    }

    private function applyScaling(array $colorData, array $adjustments): array
    {
        $result = $this->ensureRgbFormat($colorData);
        $hsl    = $this->rgbToHsl($result['r'], $result['g'], $result['b']);

        foreach ($adjustments as $key => $value) {
            $value = (float) $value;
            match ($key) {
                '$red'        => $result['r'] = $this->scaleChannel($result['r'], $value, 0, self::RGB_MAX),
                '$green'      => $result['g'] = $this->scaleChannel($result['g'], $value, 0, self::RGB_MAX),
                '$blue'       => $result['b'] = $this->scaleChannel($result['b'], $value, 0, self::RGB_MAX),
                '$hue'        => $hsl['h'] = $this->scaleChannel($hsl['h'], $value, 0, self::HUE_MAX),
                '$saturation' => $hsl['s'] = $this->scaleChannel($hsl['s'], $value, 0, self::PERCENT_MAX),
                '$lightness'  => $hsl['l'] = $this->scaleChannel($hsl['l'], $value, 0, self::PERCENT_MAX),
                '$alpha'      => $result['a'] = $this->scaleChannel($result['a'], $value, self::ALPHA_MIN, self::ALPHA_MAX),
                default       => throw new CompilationException("Unknown scaling parameter: $key"),
            };
        }

        // If HSL scaling was applied, convert back to RGB
        if (isset($adjustments['$hue']) || isset($adjustments['$saturation']) || isset($adjustments['$lightness'])) {
            $rgb = $this->hslToRgb($hsl['h'], $hsl['s'], $hsl['l']);
            $result = array_merge($rgb, ['a' => $result['a'], 'format' => ColorFormat::RGB->value]);
        }

        return $result;
    }

    private function applyChanges(array $colorData, array $adjustments): array
    {
        $result = $this->ensureRgbFormat($colorData);

        foreach ($adjustments as $key => $value) {
            $value = (float) $value;
            match ($key) {
                '$red'        => $result['r'] = $this->clamp($value, 0, self::RGB_MAX),
                '$green'      => $result['g'] = $this->clamp($value, 0, self::RGB_MAX),
                '$blue'       => $result['b'] = $this->clamp($value, 0, self::RGB_MAX),
                '$alpha'      => $result['a'] = $this->clamp($value, self::ALPHA_MIN, self::ALPHA_MAX),
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
        $hsl = $this->rgbToHsl($colorData['r'], $colorData['g'], $colorData['b']);
        $hsl[$channel] = match ($channel) {
            'h'      => $this->clamp($value, 0, self::HUE_MAX),
            's', 'l' => $this->clamp($value, 0, self::PERCENT_MAX),
        };

        $rgb = $this->hslToRgb($hsl['h'], $hsl['s'], $hsl['l']);

        return array_merge($rgb, ['a' => $colorData['a'], 'format' => ColorFormat::RGB->value]);
    }

    private function scaleChannel(float $current, float $amount, float $min, float $max): float
    {
        $range  = $max - $min;
        $scaled = $current;

        if ($amount > 0) {
            $remaining = $range - ($current - $min);
            $scaled += $remaining * ($amount / self::PERCENT_MAX);
        } elseif ($amount < 0) {
            $progress = $current - $min;
            $scaled -= $progress * (abs($amount) / self::PERCENT_MAX);
        }

        return $this->clamp($scaled, $min, $max);
    }

    private function ensureRgbFormat(array $colorData): array
    {
        if ($colorData['format'] === ColorFormat::RGB->value || $colorData['format'] === ColorFormat::RGBA->value) {
            return $colorData;
        }

        if ($colorData['format'] === ColorFormat::HSL->value || $colorData['format'] === ColorFormat::HSLA->value) {
            $rgb = $this->hslToRgb($colorData['h'], $colorData['s'], $colorData['l']);

            return array_merge($rgb, ['a' => $colorData['a'], 'format' => ColorFormat::RGB->value]);
        }

        if ($colorData['format'] === ColorFormat::HWB->value) {
            $rgb = $this->hwbToRgb($colorData['h'], $colorData['w'], $colorData['bl']);

            return array_merge($rgb, ['a' => $colorData['a'], 'format' => ColorFormat::RGB->value]);
        }

        if ($colorData['format'] === ColorFormat::LCH->value) {
            $rgb = $this->lchToRgb($colorData['l'], $colorData['c'], $colorData['h']);

            return array_merge($rgb, ['a' => $colorData['a'], 'format' => ColorFormat::RGB->value]);
        }

        if ($colorData['format'] === ColorFormat::OKLCH->value) {
            $rgb = $this->oklchToRgb($colorData['l'], $colorData['c'], $colorData['h']);

            return array_merge($rgb, ['a' => $colorData['a'], 'format' => ColorFormat::RGB->value]);
        }

        return $colorData;
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

    private function formatHslColor(array $colorData): string
    {
        $h = round($colorData['h'], 10);
        $s = round($colorData['s'], 10);
        $l = round($colorData['l'], 10);
        $a = $colorData['a'];

        if ($a < self::ALPHA_MAX) {
            return "hsla($h, $s%, $l%, $a)";
        } else {
            return "hsl($h, $s%, $l%)";
        }
    }

    private function formatHwbColor(array $colorData): string
    {
        $h  = (int) round($colorData['h']);
        $w  = (int) round($colorData['w']);
        $bl = (int) round($colorData['bl']);
        $a  = $colorData['a'];

        if ($a < self::ALPHA_MAX) {
            return "hwb($h $w% $bl% / $a)";
        } else {
            return "hwb($h $w% $bl%)";
        }
    }

    private function formatLchColor(array $colorData): string
    {
        $l = $colorData['l'] ?? 0;

        if ($l <= 1) {
            $l *= self::PERCENT_MAX;
        }

        $c = $colorData['c'] ?? 0;
        $h = $colorData['h'] ?? 0;
        $a = $colorData['a'] ?? self::ALPHA_MAX;

        if ($a < self::ALPHA_MAX) {
            return "lch($l% $c $h / $a)";
        }

        return "lch($l% $c $h)";
    }

    private function formatOklchColor(array $colorData): string
    {
        $l = $colorData['l'];

        if ($l <= 1) {
            $l *= self::PERCENT_MAX;
        }

        $l = round($l, 2);
        $c = round($colorData['c'], 4);
        $h = round($colorData['h'], 2);
        $a = $colorData['a'];

        if ($a < self::ALPHA_MAX) {
            return "oklch($l% $c $h / $a)";
        }

        return "oklch($l% $c $h)";
    }

    private function formatRgbColor(array $colorData): string
    {
        $r = (int) round($colorData['r']);
        $g = (int) round($colorData['g']);
        $b = (int) round($colorData['b']);
        $a = $colorData['a'];

        if ($a < self::ALPHA_MAX) {
            $aHex = (int) round($a * self::RGB_MAX);
            return sprintf('#%02x%02x%02x%02x', $r, $g, $b, $aHex);
        } else {
            $named = $this->getNamedColor($r, $g, $b);

            if ($named !== null) {
                return $named;
            }

            return sprintf('#%02x%02x%02x', $r, $g, $b);
        }
    }

    private function rgbToHsl(float $r, float $g, float $b): array
    {
        $key = sprintf('rgb(%d,%d,%d)', round($r), round($g), round($b));

        if (isset(self::$rgbToHslCache[$key])) {
            return self::$rgbToHslCache[$key];
        }

        $r /= self::RGB_MAX;
        $g /= self::RGB_MAX;
        $b /= self::RGB_MAX;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);

        $l = ($max + $min) / 2;
        $s = 0;
        $h = 0;

        if ($max !== $min) {
            $d = $max - $min;
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
            $h = match (true) {
                $max === $r => ($g - $b) / $d + ($g < $b ? 6 : 0),
                $max === $g => ($b - $r) / $d + 2,
                $max === $b => ($r - $g) / $d + 4,
                default => 0,
            } * 60;
        }

        $result = [
            'h'      => $h,
            's'      => $s * self::PERCENT_MAX,
            'l'      => $l * self::PERCENT_MAX,
            'format' => ColorFormat::HSL->value,
        ];

        self::$rgbToHslCache[$key] = $result;

        return $result;
    }

    private function hslToRgb(float $h, float $s, float $l): array
    {
        $key = sprintf('hsl(%.1f,%.1f,%.1f)', $h, $s, $l);

        if (isset(self::$hslToRgbCache[$key])) {
            return self::$hslToRgbCache[$key];
        }

        $s /= self::PERCENT_MAX;
        $l /= self::PERCENT_MAX;

        $c  = (1 - abs(2 * $l - 1)) * $s;
        $hp = $h / 60;

        $x = $c * (1 - abs(fmod($hp, 2) - 1));
        $m = $l - $c / 2;

        $rgb = match (true) {
            $hp >= 0 && $hp < 1 => [$c, $x, 0],
            $hp < 2 => [$x, $c, 0],
            $hp < 3 => [0, $c, $x],
            $hp < 4 => [0, $x, $c],
            $hp < 5 => [$x, 0, $c],
            default => [$c, 0, $x],
        };

        $result = [
            'r'      => ($rgb[0] + $m) * self::RGB_MAX,
            'g'      => ($rgb[1] + $m) * self::RGB_MAX,
            'b'      => ($rgb[2] + $m) * self::RGB_MAX,
            'a'      => self::ALPHA_MAX,
            'format' => ColorFormat::RGB->value,
        ];

        self::$hslToRgbCache[$key] = $result;

        return $result;
    }

    private function rgbToHwb(float $r, float $g, float $b): array
    {
        $r /= self::RGB_MAX;
        $g /= self::RGB_MAX;
        $b /= self::RGB_MAX;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);

        $h = 0;

        if ($max !== $min) {
            $h = match (true) {
                $max === $r => ($g - $b) / ($max - $min) + ($g < $b ? 6 : 0),
                $max === $g => ($b - $r) / ($max - $min) + 2,
                $max === $b => ($r - $g) / ($max - $min) + 4,
                default => 0,
            } * 60;
        }

        return [
            'h'      => round($h, 5),
            'w'      => round($min * self::PERCENT_MAX, 5),
            'bl'     => round((1 - $max) * self::PERCENT_MAX, 5),
            'format' => ColorFormat::HWB->value,
        ];
    }

    private function hwbToRgb(float $h, float $w, float $bl): array
    {
        $w /= self::PERCENT_MAX;
        $bl /= self::PERCENT_MAX;

        $chroma = max(self::ALPHA_MIN, self::ALPHA_MAX - $w - $bl);

        $hsl = $this->hslToRgb($h, self::PERCENT_MAX, 50.0);

        $hsl['r'] = min(self::RGB_MAX, max(self::ALPHA_MIN, round($hsl['r'])));
        $hsl['g'] = min(self::RGB_MAX, max(self::ALPHA_MIN, round($hsl['g'])));
        $hsl['b'] = min(self::RGB_MAX, max(self::ALPHA_MIN, round($hsl['b'])));

        $r = ($hsl['r'] / self::RGB_MAX * $chroma + $w) * self::RGB_MAX;
        $g = ($hsl['g'] / self::RGB_MAX * $chroma + $w) * self::RGB_MAX;
        $b = ($hsl['b'] / self::RGB_MAX * $chroma + $w) * self::RGB_MAX;

        return [
            'r'      => round($r),
            'g'      => round($g),
            'b'      => round($b),
            'a'      => self::ALPHA_MAX,
            'format' => ColorFormat::RGB->value,
        ];
    }

    private function linearizeChannel(float $val): float
    {
        return $val > 0.04045 ? (($val + 0.055) / 1.055) ** 2.4 : $val / 12.92;
    }

    private function unLinearizeChannel(float $val): float
    {
        return $val > 0.0031308 ? 1.055 * $val ** (1 / 2.4) - 0.055 : 12.92 * $val;
    }

    private function rgbToXyz(float $r, float $g, float $b): array
    {
        $r /= self::RGB_MAX;
        $g /= self::RGB_MAX;
        $b /= self::RGB_MAX;

        $r = $this->linearizeChannel($r);
        $g = $this->linearizeChannel($g);
        $b = $this->linearizeChannel($b);

        $x = $r * 0.4124 + $g * 0.3576 + $b * 0.1805;
        $y = $r * 0.2126 + $g * 0.7152 + $b * 0.0722;
        $z = $r * 0.0193 + $g * 0.1192 + $b * 0.9505;

        return [
            'x' => $x * self::PERCENT_MAX,
            'y' => $y * self::PERCENT_MAX,
            'z' => $z * self::PERCENT_MAX,
        ];
    }

    private function xyzToRgb(float $x, float $y, float $z): array
    {
        $x /= self::PERCENT_MAX;
        $y /= self::PERCENT_MAX;
        $z /= self::PERCENT_MAX;

        $r = $x * 3.2406 + $y * -1.5372 + $z * -0.4986;
        $g = $x * -0.9689 + $y * 1.8758 + $z * 0.0415;
        $b = $x * 0.0557 + $y * -0.2040 + $z * 1.0570;

        $r = $this->unLinearizeChannel($r);
        $g = $this->unLinearizeChannel($g);
        $b = $this->unLinearizeChannel($b);

        return [
            'r'      => $this->clamp($r * self::RGB_MAX, self::ALPHA_MIN, self::RGB_MAX),
            'g'      => $this->clamp($g * self::RGB_MAX, self::ALPHA_MIN, self::RGB_MAX),
            'b'      => $this->clamp($b * self::RGB_MAX, self::ALPHA_MIN, self::RGB_MAX),
            'a'      => self::ALPHA_MAX,
            'format' => ColorFormat::RGB->value,
        ];
    }

    private function getNamedColor(int $r, int $g, int $b): ?string
    {
        $hex = strtolower(sprintf('#%02x%02x%02x', $r, $g, $b));

        if (empty(self::$hexToNameCache)) {
            self::$hexToNameCache = array_flip(self::NAMED_COLORS);
        }

        return self::$hexToNameCache[$hex] ?? null;
    }

    private function getColorSpace(string $format): string
    {
        return match ($format) {
            ColorFormat::HSL->value,
            ColorFormat::HSLA->value  => ColorFormat::HSL->value,
            ColorFormat::HWB->value   => ColorFormat::HWB->value,
            ColorFormat::LCH->value   => ColorFormat::LCH->value,
            ColorFormat::OKLCH->value => ColorFormat::OKLCH->value,
            default                   => ColorFormat::RGB->value,
        };
    }

    private function clamp(float $value, float $min, float $max): float
    {
        return min(max($value, $min), $max);
    }

    private function parseAlpha(string $alphaStr): float
    {
        if (str_contains($alphaStr, '%')) {
            return (float) rtrim($alphaStr, '%') / self::PERCENT_MAX;
        }

        return (float) $alphaStr;
    }

    private function labFunction(float $t): float
    {
        $epsilon = self::LAB_EPSILON;
        $kappa   = self::LAB_KAPPA;

        if ($t > $epsilon) {
            return $t ** (1 / 3);
        }

        return ($kappa * $t + 16) / 116;
    }

    private function xyzToLab(float $x, float $y, float $z): array
    {
        $x /= self::XYZ_REF_X;
        $y /= self::XYZ_REF_Y;
        $z /= self::XYZ_REF_Z;

        $x = $this->labFunction($x);
        $y = $this->labFunction($y);
        $z = $this->labFunction($z);

        $l = (116 * $y) - 16;
        $a = 500 * ($x - $y);
        $b = 200 * ($y - $z);

        return ['l' => $l, 'a' => $a, 'b' => $b];
    }

    private function labToLch(float $l, float $a, float $b): array
    {
        $c = sqrt($a * $a + $b * $b);
        $h = atan2($b, $a) * self::HUE_SHIFT / M_PI;

        if ($h < 0) {
            $h += self::HUE_MAX;
        }

        return ['l' => $l, 'c' => $c, 'h' => $h];
    }

    private function lchToLab(float $l, float $c, float $h): array
    {
        $hRad = $h * M_PI / self::HUE_SHIFT;

        $a = $c * cos($hRad);
        $b = $c * sin($hRad);

        return ['l' => $l, 'a' => $a, 'b' => $b];
    }

    private function rgbToLch(int $r, int $g, int $b): array
    {
        // RGB → XYZ → LAB → LCH
        $xyz = $this->rgbToXyz($r, $g, $b);
        $lab = $this->xyzToLab($xyz['x'], $xyz['y'], $xyz['z']);

        return $this->labToLch($lab['l'], $lab['a'], $lab['b']);
    }

    private function labInverseFunction(float $t): float
    {
        $epsilon = self::LAB_EPSILON;
        $kappa   = self::LAB_KAPPA;

        $t3 = $t * $t * $t;

        if ($t3 > $epsilon) {
            return $t3;
        }

        return (116 * $t - 16) / $kappa;
    }

    private function labToXyz(float $l, float $a, float $b): array
    {
        $fy = ($l + 16) / 116;
        $fx = $a / 500 + $fy;
        $fz = $fy - $b / 200;

        $x = $this->labInverseFunction($fx) * self::XYZ_REF_X;
        $y = $this->labInverseFunction($fy) * self::XYZ_REF_Y;
        $z = $this->labInverseFunction($fz) * self::XYZ_REF_Z;

        return ['x' => $x, 'y' => $y, 'z' => $z];
    }

    private function lchToRgb(float $l, float $c, float $h): array
    {
        // LCH → LAB → XYZ → RGB
        $lab = $this->lchToLab($l, $c, $h);
        $xyz = $this->labToXyz($lab['l'], $lab['a'], $lab['b']);

        return $this->xyzToRgb($xyz['x'], $xyz['y'], $xyz['z']);
    }

    private function rgbToOklch(float $r, float $g, float $b): array
    {
        $r /= self::RGB_MAX;
        $g /= self::RGB_MAX;
        $b /= self::RGB_MAX;

        $r = $this->linearizeChannel($r);
        $g = $this->linearizeChannel($g);
        $b = $this->linearizeChannel($b);

        $l = 0.4122214708 * $r + 0.5363325363 * $g + 0.0514459929 * $b;
        $m = 0.2119034982 * $r + 0.6806995451 * $g + 0.1073969566 * $b;
        $s = 0.0883024619 * $r + 0.2817188376 * $g + 0.6299787005 * $b;

        $l **= 1 / 3;
        $m **= 1 / 3;
        $s **= 1 / 3;

        $L = 0.2104542553 * $l + 0.7936177850 * $m - 0.0040720468 * $s;
        $a = 1.9779984951 * $l - 2.4285922050 * $m + 0.4505937099 * $s;
        $b_val = 0.0259040371 * $l + 0.7827717662 * $m - 0.8086757660 * $s;

        $C = sqrt($a * $a + $b_val * $b_val);
        $H = atan2($b_val, $a) * self::HUE_SHIFT / M_PI;
        if ($H < 0) {
            $H += self::HUE_MAX;
        }

        return [
            'l'      => $L * self::PERCENT_MAX,
            'c'      => $C,
            'h'      => $H,
            'format' => ColorFormat::OKLCH->value,
        ];
    }

    private function oklchToRgb(float $l, float $c, float $h): array
    {
        $l /= self::PERCENT_MAX;

        $h_rad = $h * M_PI / self::HUE_SHIFT;

        $a = $c * cos($h_rad);
        $b = $c * sin($h_rad);

        $l_lms = $l + 0.3963377776 * $a + 0.2158037573 * $b;
        $m_lms = $l - 0.1055613458 * $a - 0.0638541728 * $b;
        $s_lms = $l - 0.0894841775 * $a - 1.2914855480 * $b;

        $l_lms **= 3;
        $m_lms **= 3;
        $s_lms **= 3;

        $r     = +4.0767416621 * $l_lms - 3.3077115913 * $m_lms + 0.2309699292 * $s_lms;
        $g     = -1.2684380046 * $l_lms + 2.6097574011 * $m_lms - 0.3413193965 * $s_lms;
        $b_val = -0.0041960863 * $l_lms - 0.7034186147 * $m_lms + 1.7076147010 * $s_lms;

        $r     = $this->unLinearizeChannel($r);
        $g     = $this->unLinearizeChannel($g);
        $b_val = $this->unLinearizeChannel($b_val);

        return [
            'r'      => $this->clamp($r * self::RGB_MAX, 0, self::RGB_MAX),
            'g'      => $this->clamp($g * self::RGB_MAX, 0, self::RGB_MAX),
            'b'      => $this->clamp($b_val * self::RGB_MAX, 0, self::RGB_MAX),
            'a'      => self::ALPHA_MAX,
            'format' => ColorFormat::RGB->value,
        ];
    }

    private function parseHueValue(string $hueStr): float
    {
        return match (true) {
            str_contains($hueStr, 'rad')  => (float) rtrim($hueStr, 'rad') * self::HUE_SHIFT / M_PI,
            str_contains($hueStr, 'grad') => (float) rtrim($hueStr, 'grad') * self::HUE_MAX / 400,
            str_contains($hueStr, 'turn') => (float) rtrim($hueStr, 'turn') * self::HUE_MAX,
            str_contains($hueStr, 'deg')  => (float) rtrim($hueStr, 'deg'),
            default                       => (float) $hueStr,
        };
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

    private function convertToSpace(array $colorData, string $targetSpace): array
    {
        $originalFormat = $colorData['format'] ?? ColorFormat::RGB->value;
        $targetFormat   = ColorFormat::tryFrom($targetSpace);
        $currentFormat  = ColorFormat::tryFrom($originalFormat);

        if ($currentFormat && $targetFormat && $currentFormat->isCompatibleWith($targetFormat)) {
            return $colorData;
        }

        switch ($targetSpace) {
            case ColorFormat::HSL->value:
            case ColorFormat::HSLA->value:
                $converted = $this->rgbToHsl($colorData['r'], $colorData['g'], $colorData['b']);
                break;

            case ColorFormat::HWB->value:
                $converted = $this->rgbToHwb($colorData['r'], $colorData['g'], $colorData['b']);
                break;

            case ColorFormat::LCH->value:
                $converted = $this->rgbToLch($colorData['r'], $colorData['g'], $colorData['b']);
                break;

            case ColorFormat::OKLCH->value:
                $converted = $this->rgbToOklch($colorData['r'], $colorData['g'], $colorData['b']);
                break;

            case ColorFormat::RGB->value:
                if ($colorData['format'] === ColorFormat::LCH->value) {
                    $rgb = $this->lchToRgb($colorData['l'], $colorData['c'], $colorData['h']);

                    return array_merge($rgb, ['a' => $colorData['a']]);
                }

                $converted = $this->ensureRgbFormat($colorData);
                break;

            default:
                $converted = $this->ensureRgbFormat($colorData);
                break;
        }

        return $this->withAlpha($converted, $colorData['a'] ?? self::ALPHA_MAX);
    }

    private function withAlpha(array $colorData, float $alpha): array
    {
        return array_merge($colorData, ['a' => $alpha]);
    }
}
