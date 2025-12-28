<?php

declare(strict_types=1);

namespace DartSass\Modules;

use InvalidArgumentException;

use function array_flip;
use function array_merge;
use function round;
use function sprintf;
use function strtolower;

class ColorSerializer
{
    public const RGB_MAX = 255;

    public const PERCENT_MAX = 100;

    public const HUE_MAX = 360;

    public const HUE_SHIFT = 180;

    public const ALPHA_MAX = 1.0;

    public const ALPHA_MIN = 0.0;

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

    private static array $hexToNameCache = [];

    public static function format(ColorFormat $format, SassColor $sassColor): string
    {
        $hasAlpha = $sassColor->getAlpha() < self::ALPHA_MAX;

        return match ($format) {
            ColorFormat::HSL,
            ColorFormat::HSLA  => self::formatHsl($sassColor, $hasAlpha),
            ColorFormat::HWB   => self::formatHwb($sassColor, $hasAlpha),
            ColorFormat::LAB,
            ColorFormat::LABA  => self::formatLab($sassColor, $hasAlpha),
            ColorFormat::LCH   => self::formatLch($sassColor, $hasAlpha),
            ColorFormat::OKLCH => self::formatOklch($sassColor, $hasAlpha),
            ColorFormat::RGB,
            ColorFormat::RGBA  => self::formatRgb($sassColor, $hasAlpha),
            ColorFormat::XYZ,
            ColorFormat::XYZA  => self::formatXyz($sassColor, $hasAlpha),
            ColorFormat::HEX   => self::formatHex($sassColor),
            ColorFormat::HEXA  => self::formatHexa($sassColor),
        };
    }

    private static function formatHsl(SassColor $sassColor, bool $hasAlpha): string
    {
        $h = round($sassColor->getHue(), 10);
        $s = round($sassColor->getSaturation(), 10);
        $l = round($sassColor->getLightness(), 10);
        $a = $sassColor->getAlpha();

        // Normalize hue to be within [0, 360) range
        while ($h < 0) {
            $h += self::HUE_MAX;
        }

        while ($h >= self::HUE_MAX) {
            $h -= self::HUE_MAX;
        }

        return $hasAlpha
            ? "hsla($h, $s%, $l%, $a)"
            : "hsl($h, $s%, $l%)";
    }

    private static function formatHwb(SassColor $sassColor, bool $hasAlpha): string
    {
        $h  = (int) round($sassColor->getHue());
        $w  = (int) round($sassColor->getWhiteness());
        $bl = (int) round($sassColor->getBlackness());
        $a  = $sassColor->getAlpha();

        return $hasAlpha
            ? "hwb($h $w% $bl% / $a)"
            : "hwb($h $w% $bl%)";
    }

    private static function formatLab(SassColor $sassColor, bool $hasAlpha): string
    {
        $l = round($sassColor->getLabL(), 2);
        $a = round($sassColor->getLabA(), 2);
        $b = round($sassColor->getLabB(), 2);

        $alpha = $sassColor->getAlpha();

        return $hasAlpha
            ? "lab($l% $a $b / $alpha)"
            : "lab($l% $a $b)";
    }

    private static function formatLch(SassColor $sassColor, bool $hasAlpha): string
    {
        $l = $sassColor->getLightness();

        if ($l <= 1) {
            $l *= self::PERCENT_MAX;
        }

        $c = $sassColor->getChroma();
        $h = $sassColor->getHue();
        $a = $sassColor->getAlpha();

        return $hasAlpha
            ? "lch($l% $c $h / $a)"
            : "lch($l% $c $h)";
    }

    private static function formatOklch(SassColor $sassColor, bool $hasAlpha): string
    {
        $l = $sassColor->getLightness();

        if ($l <= 1) {
            $l *= self::PERCENT_MAX;
        }

        $l = round($l, 2);
        $c = round($sassColor->getChroma(), 4);
        $h = $sassColor->getHue();
        $h = round($h, 2);
        $a = $sassColor->getAlpha();

        return $hasAlpha
            ? "oklch($l% $c $h / $a)"
            : "oklch($l% $c $h)";
    }

    private static function formatRgb(SassColor $sassColor, bool $hasAlpha): string
    {
        $r = (int) round($sassColor->getRed());
        $g = (int) round($sassColor->getGreen());
        $b = (int) round($sassColor->getBlue());
        $a = $sassColor->getAlpha();

        if ($hasAlpha) {
            $aHex = (int) round($a * self::RGB_MAX);

            return sprintf('#%02x%02x%02x%02x', $r, $g, $b, $aHex);
        }

        $named = self::getNamedColor($r, $g, $b);

        return $named ?? sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    private static function formatXyz(SassColor $sassColor, bool $hasAlpha): string
    {
        $x = round($sassColor->getX(), 10);
        $y = round($sassColor->getY(), 10);
        $z = round($sassColor->getZ(), 10);
        $a = $sassColor->getAlpha();

        return $hasAlpha
            ? "color(xyz $x $y $z / $a)"
            : "color(xyz $x $y $z)";
    }

    private static function formatHex(SassColor $sassColor): string
    {
        $r = (int) round($sassColor->getRed());
        $g = (int) round($sassColor->getGreen());
        $b = (int) round($sassColor->getBlue());

        $named = self::getNamedColor($r, $g, $b);

        return $named ?? sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    private static function formatHexa(SassColor $sassColor): string
    {
        $r = (int) round($sassColor->getRed());
        $g = (int) round($sassColor->getGreen());
        $b = (int) round($sassColor->getBlue());
        $a = (int) round($sassColor->getAlpha() * self::RGB_MAX);

        return sprintf('#%02x%02x%02x%02x', $r, $g, $b, $a);
    }

    public static function getNamedColor(int $r, int $g, int $b): ?string
    {
        $hex = strtolower(sprintf('#%02x%02x%02x', $r, $g, $b));

        if (empty(self::$hexToNameCache)) {
            self::$hexToNameCache = array_flip(self::NAMED_COLORS);
        }

        return self::$hexToNameCache[$hex] ?? null;
    }

    public static function ensureRgbFormat(array $colorData): array
    {
        if ($colorData['format'] === ColorFormat::RGB->value || $colorData['format'] === ColorFormat::RGBA->value) {
            return $colorData;
        }

        $alpha = $colorData['a'] ?? self::ALPHA_MAX;

        $rgb = match ($colorData['format']) {
            ColorFormat::HSL->value,
            ColorFormat::HSLA->value  => ColorConverter::HSL->toRgb($colorData['h'], $colorData['s'], $colorData['l']),
            ColorFormat::HWB->value   => ColorConverter::HWB->toRgb($colorData['h'], $colorData['w'], $colorData['bl']),
            ColorFormat::LAB->value,
            ColorFormat::LABA->value  => ColorConverter::LAB->toRgb($colorData['lab_l'], $colorData['lab_a'], $colorData['lab_b']),
            ColorFormat::LCH->value   => ColorConverter::LCH->toRgb($colorData['l'], $colorData['c'], $colorData['h']),
            ColorFormat::OKLCH->value => ColorConverter::OKLCH->toRgb($colorData['l'], $colorData['c'], $colorData['h']),
            ColorFormat::XYZ->value,
            ColorFormat::XYZA->value  => ColorConverter::XYZ->toRgb($colorData['x'], $colorData['y'], $colorData['z']),
            default                   => throw new InvalidArgumentException('Unsupported color format: ' . $colorData['format']),
        };

        return array_merge($rgb, [
            'a'      => $alpha,
            'format' => self::resolveRgbFormat($colorData),
        ]);
    }

    private static function resolveRgbFormat(array $colorData): string
    {
        $originalFormat = $colorData['format'] ?? ColorFormat::RGB->value;

        $alpha = $colorData['a'] ?? self::ALPHA_MAX;

        if ($originalFormat !== ColorFormat::RGBA->value) {
            return ColorFormat::RGB->value;
        }

        return $alpha < self::ALPHA_MAX
            ? ColorFormat::RGBA->value
            : ColorFormat::RGB->value;
    }
}
