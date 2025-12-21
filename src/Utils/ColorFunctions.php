<?php

declare(strict_types=1);

namespace DartSass\Utils;

use InvalidArgumentException;
use DartSass\Exceptions\CompilationException;

use function abs;
use function array_flip;
use function array_merge;
use function fmod;
use function hexdec;
use function in_array;
use function ltrim;
use function max;
use function min;
use function preg_match;
use function round;
use function sprintf;
use function strlen;
use function substr;
use function trim;

class ColorFunctions
{
    private static array $rgbToHslCache = [];

    private static array $hslToRgbCache = [];

    private static array $hexToNameCache = [];

    private const HEX_PATTERN = '/^#([0-9a-fA-F]{3,8})$/';

    private const RGB_PATTERN = '/^rgb\((\d{1,3}),\s*(\d{1,3}),\s*(\d{1,3})\)$/';

    private const RGBA_PATTERN = '/^rgba\((\d{1,3}),\s*(\d{1,3}),\s*(\d{1,3}),\s*([0-1]?\.\d+|0|1)\)$/';

    private const HSL_PATTERN = '/^hsl\((\d{1,3}),\s*(\d{1,3})%,\s*(\d{1,3})%\)$/';

    private const HSLA_PATTERN = '/^hsla\((\d{1,3}),\s*(\d{1,3})%,\s*(\d{1,3})%,\s*([0-1]?\.\d+|0|1)\)$/';

    private const HWB_PATTERN = '/^hwb\((\d{1,3}),\s*(\d{1,3})%,\s*(\d{1,3})%\)$/';

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
    ];

    private const NAMED_COLORS = [
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

    public function mix(string $color1, string $color2, float $weight = 0.5): string
    {
        $c1 = $this->parseColor($color1);
        $c2 = $this->parseColor($color2);
        $weight = $this->clamp($weight, 0.0, 1.0);

        $r = round($c1['r'] * $weight + $c2['r'] * (1 - $weight));
        $g = round($c1['g'] * $weight + $c2['g'] * (1 - $weight));
        $b = round($c1['b'] * $weight + $c2['b'] * (1 - $weight));
        $a = $c1['a'] * $weight + $c2['a'] * (1 - $weight);

        return $this->formatColor([
            'r'      => $this->clamp($r, 0, 255),
            'g'      => $this->clamp($g, 0, 255),
            'b'      => $this->clamp($b, 0, 255),
            'a'      => $this->clamp($a, 0.0, 1.0),
            'format' => $c1['a'] < 1.0 || $c2['a'] < 1.0 ? 'rgba' : 'rgb',
        ]);
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

    public function scale(string $color, array $adjustments): string
    {
        $colorData = $this->parseColor($color);
        $colorData = $this->applyScaling($colorData, $adjustments);

        return $this->formatColor($colorData);
    }

    public function change(string $color, array $adjustments): string
    {
        $colorData = $this->parseColor($color);
        $colorData = $this->applyChanges($colorData, $adjustments);

        return $this->formatColor($colorData);
    }

    public function hsl(float $h, float $s, float $l, ?float $a = null): string
    {
        $a ??= 1.0;
        $rgb = $this->hslToRgb($h, $s, $l);

        return $this->formatColor([
            'r'      => $rgb['r'],
            'g'      => $rgb['g'],
            'b'      => $rgb['b'],
            'a'      => $a,
            'format' => $a < 1.0 ? 'rgba' : 'rgb',
        ]);
    }

    public function hwb(float $h, float $w, float $bl, ?float $a = null): string
    {
        $a ??= 1.0;
        $rgb = $this->hwbToRgb($h, $w, $bl);

        return $this->formatColor(array_merge($rgb, ['a' => $a]));
    }

    public function parseColor(string $color): array
    {
        $color = trim($color);

        if (isset(self::NAMED_COLORS[$color])) {
            return $this->parseHex(substr(self::NAMED_COLORS[$color], 1));
        }

        if (preg_match(self::HEX_PATTERN, $color, $matches)) {
            return $this->parseHex($matches[1]);
        }

        if (preg_match(self::RGB_PATTERN, $color, $matches)) {
            return $this->parseRgb($matches);
        }

        if (preg_match(self::RGBA_PATTERN, $color, $matches)) {
            return $this->parseRgba($matches);
        }

        if (preg_match(self::HSL_PATTERN, $color, $matches)) {
            return $this->parseHsl($matches);
        }

        if (preg_match(self::HSLA_PATTERN, $color, $matches)) {
            return $this->parseHsla($matches);
        }

        if (preg_match(self::HWB_PATTERN, $color, $matches)) {
            return $this->parseHwb($matches);
        }

        throw new CompilationException("Invalid color format: $color");
    }

    private function parseHex(string $hex): array
    {
        if (strlen($hex) === 3 || strlen($hex) === 4) {
            $r = hexdec($hex[0] . $hex[0]);
            $g = hexdec($hex[1] . $hex[1]);
            $b = hexdec($hex[2] . $hex[2]);
            $a = strlen($hex) === 4 ? hexdec($hex[3] . $hex[3]) / 255 : 1.0;
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
            $a = strlen($hex) === 8 ? hexdec(substr($hex, 6, 2)) / 255 : 1.0;
        }

        return [
            'r'      => $r,
            'g'      => $g,
            'b'      => $b,
            'a'      => $a,
            'format' => $a < 1.0 ? 'rgba' : 'rgb',
        ];
    }

    private function parseRgb(array $matches): array
    {
        return [
            'r'      => $this->clamp((int) $matches[1], 0, 255),
            'g'      => $this->clamp((int) $matches[2], 0, 255),
            'b'      => $this->clamp((int) $matches[3], 0, 255),
            'a'      => 1.0,
            'format' => 'rgb',
        ];
    }

    private function parseRgba(array $matches): array
    {
        $r = (int) $matches[1];
        $g = (int) $matches[2];
        $b = (int) $matches[3];
        $a = (float) $matches[4];

        if ($a < 0.0 || $a > 1.0) {
            throw new CompilationException("Invalid alpha value: $a");
        }

        return [
            'r'      => $this->clamp($r, 0, 255),
            'g'      => $this->clamp($g, 0, 255),
            'b'      => $this->clamp($b, 0, 255),
            'a'      => $this->clamp($a, 0.0, 1.0),
            'format' => 'rgba',
        ];
    }

    private function parseHsl(array $matches): array
    {
        $h = (int) $matches[1];
        $s = (int) $matches[2];
        $l = (int) $matches[3];

        if ($s < 0 || $s > 100) {
            throw new CompilationException("Invalid saturation value: $s");
        }

        if ($l < 0 || $l > 100) {
            throw new CompilationException("Invalid lightness value: $l");
        }

        return [
            'h'      => $this->clamp($h, 0, 360),
            's'      => $this->clamp($s, 0, 100),
            'l'      => $this->clamp($l, 0, 100),
            'a'      => 1.0,
            'format' => 'hsl',
        ];
    }

    private function parseHsla(array $matches): array
    {
        $h = (int) $matches[1];
        $s = (int) $matches[2];
        $l = (int) $matches[3];
        $a = (float) $matches[4];

        if ($s < 0 || $s > 100) {
            throw new CompilationException("Invalid saturation value: $s");
        }

        if ($l < 0 || $l > 100) {
            throw new CompilationException("Invalid lightness value: $l");
        }

        if ($a < 0.0 || $a > 1.0) {
            throw new CompilationException("Invalid alpha value: $a");
        }

        return [
            'h'      => $this->clamp($h, 0, 360),
            's'      => $this->clamp($s, 0, 100),
            'l'      => $this->clamp($l, 0, 100),
            'a'      => $this->clamp($a, 0.0, 1.0),
            'format' => 'hsla',
        ];
    }

    private function parseHwb(array $matches): array
    {
        return [
            'h'      => $this->clamp((int) $matches[1], 0, 360),
            'w'      => $this->clamp((int) $matches[2], 0, 100) / 100,
            'bl'     => $this->clamp((int) $matches[3], 0, 100) / 100,
            'a'      => 1.0,
            'format' => 'hwb',
        ];
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
                '$blue' => $rgbAdjustments['rgb'][$this->keyToChannel($key)] = $valueNumber,
                '$hue',
                '$saturation',
                '$lightness' => $hslAdjustments['hsl'][$this->keyToHslChannel($key)] = $valueNumber,
                '$alpha' => $rgbAdjustments['alpha'] = ($rgbAdjustments['alpha'] ?? $result['a']) + $valueNumber,
                '$whiteness',
                '$blackness',
                '$x',
                '$y',
                '$z',
                '$chroma',
                '$space' => $rgbAdjustments[ltrim($key, '$')] = $valueNumber,
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
                'rgb' => (function () use (&$result, $value): void {
                    foreach ($value as $channel => $adjustment) {
                        $result[$channel] = $this->clamp($result[$channel] + $adjustment, 0, 255);
                    }
                })(),
                'alpha' => $result['a'] = $this->clamp($value, 0.0, 1.0),
                default => null,
            };
        }

        // Handle HWB adjustments (whiteness/blackness)
        if (isset($adjustments['whiteness']) || isset($adjustments['blackness'])) {
            $hwb = $this->rgbToHwb($result['r'], $result['g'], $result['b']);

            $w_percent = ($hwb['w'] ?? 0.0) + ($adjustments['whiteness'] ?? 0.0);
            $bl_percent = ($hwb['bl'] ?? 0.0) + ($adjustments['blackness'] ?? 0.0);

            $w_percent = $this->clamp($w_percent, 0.0, 100.0);
            $bl_percent = $this->clamp($bl_percent, 0.0, 100.0);

            $rgb = $this->hwbToRgb($hwb['h'] ?? 0.0, $w_percent, $bl_percent);
            $result = array_merge($rgb, ['a' => $result['a'], 'format' => 'rgb']);
        }

        // Handle XYZ adjustments
        if (isset($adjustments['x']) || isset($adjustments['y']) || isset($adjustments['z'])) {
            $xyz = $this->rgbToXyz($result['r'], $result['g'], $result['b']);

            $x = $this->clamp(($xyz['x'] ?? 0.0) + ($adjustments['x'] ?? 0.0), 0.0, 100.0);
            $y = $this->clamp(($xyz['y'] ?? 0.0) + ($adjustments['y'] ?? 0.0), 0.0, 100.0);
            $z = $this->clamp(($xyz['z'] ?? 0.0) + ($adjustments['z'] ?? 0.0), 0.0, 100.0);

            $result = array_merge($this->xyzToRgb($x, $y, $z), ['a' => $result['a'], 'format' => 'rgb']);
        }

        // Handle chroma adjustments
        if (isset($adjustments['chroma'])) {
            $hsl = $this->rgbToHsl($result['r'], $result['g'], $result['b']);
            $currentLightness = $hsl['l'];
            $chromaAdjustment = $adjustments['chroma'];
            $newLightness = $this->clamp($currentLightness + ($chromaAdjustment * 0.5), 0, 100);

            // Only adjust if lightness actually changes
            if (abs($newLightness - $currentLightness) > 0.1) {
                $rgb = $this->hslToRgb($hsl['h'], $hsl['s'], $newLightness);
                $result = array_merge($rgb, ['a' => $result['a'], 'format' => 'rgb']);
            }
        }

        return $result;
    }

    private function applyHslAdjustments(array $colorData, array $adjustments): array
    {
        $hsl = $this->rgbToHsl($colorData['r'], $colorData['g'], $colorData['b']);

        foreach ($adjustments['hsl'] as $channel => $adjustment) {
            match ($channel) {
                'h' => $hsl['h'] = $this->clamp($hsl['h'] + $adjustment, 0, 360),
                's' => $hsl['s'] = $this->clamp($hsl['s'] + $adjustment, 0, 100),
                'l' => $hsl['l'] = $this->clamp($hsl['l'] + $adjustment, 0, 100),
            };
        }

        $rgb = $this->hslToRgb($hsl['h'], $hsl['s'], $hsl['l']);

        return array_merge($rgb, ['a' => $colorData['a'], 'format' => 'rgb']);
    }

    private function applyScaling(array $colorData, array $adjustments): array
    {
        $result = $this->ensureRgbFormat($colorData);
        $hsl = $this->rgbToHsl($result['r'], $result['g'], $result['b']);

        foreach ($adjustments as $key => $value) {
            $value = (float) $value;
            match ($key) {
                '$red'        => $result['r'] = $this->scaleChannel($result['r'], $value, 0, 255),
                '$green'      => $result['g'] = $this->scaleChannel($result['g'], $value, 0, 255),
                '$blue'       => $result['b'] = $this->scaleChannel($result['b'], $value, 0, 255),
                '$hue'        => $hsl['h'] = $this->scaleChannel($hsl['h'], $value, 0, 360),
                '$saturation' => $hsl['s'] = $this->scaleChannel($hsl['s'], $value, 0, 100),
                '$lightness'  => $hsl['l'] = $this->scaleChannel($hsl['l'], $value, 0, 100),
                '$alpha'      => $result['a'] = $this->scaleChannel($result['a'], $value, 0.0, 1.0),
                default       => throw new CompilationException("Unknown scaling parameter: $key"),
            };
        }

        // If HSL scaling was applied, convert back to RGB
        if (isset($adjustments['$hue']) || isset($adjustments['$saturation']) || isset($adjustments['$lightness'])) {
            $rgb = $this->hslToRgb($hsl['h'], $hsl['s'], $hsl['l']);
            $result = array_merge($rgb, ['a' => $result['a'], 'format' => 'rgb']);
        }

        return $result;
    }

    private function applyChanges(array $colorData, array $adjustments): array
    {
        $result = $this->ensureRgbFormat($colorData);

        foreach ($adjustments as $key => $value) {
            $value = (float) $value;
            match ($key) {
                '$red'        => $result['r'] = $this->clamp($value, 0, 255),
                '$green'      => $result['g'] = $this->clamp($value, 0, 255),
                '$blue'       => $result['b'] = $this->clamp($value, 0, 255),
                '$alpha'      => $result['a'] = $this->clamp($value, 0.0, 1.0),
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
            'h' => $this->clamp($value, 0, 360),
            's', 'l' => $this->clamp($value, 0, 100),
        };

        $rgb = $this->hslToRgb($hsl['h'], $hsl['s'], $hsl['l']);

        return array_merge($rgb, ['a' => $colorData['a'], 'format' => 'rgb']);
    }

    private function scaleChannel(float $current, float $amount, float $min, float $max): float
    {
        $range = $max - $min;
        $scaled = $current;
        if ($amount > 0) {
            $remaining = $range - ($current - $min);
            $scaled += $remaining * ($amount / 100);
        } elseif ($amount < 0) {
            $progress = $current - $min;
            $scaled -= $progress * (abs($amount) / 100);
        }

        return $this->clamp($scaled, $min, $max);
    }

    private function ensureRgbFormat(array $colorData): array
    {
        if (in_array($colorData['format'], ['rgb', 'rgba'], true)) {
            return $colorData;
        }

        if (in_array($colorData['format'], ['hsl', 'hsla'], true)) {
            $rgb = $this->hslToRgb($colorData['h'], $colorData['s'], $colorData['l']);

            return array_merge($rgb, ['a' => $colorData['a'], 'format' => 'rgb']);
        }

        if ($colorData['format'] === 'hwb') {
            $rgb = $this->hwbToRgb($colorData['h'], $colorData['w'], $colorData['bl']);

            return array_merge($rgb, ['a' => $colorData['a'], 'format' => 'rgb']);
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

    private function formatColor(array $colorData): string
    {
        if (! in_array($colorData['format'], ['rgb', 'rgba'], true)) {
            $colorData = $this->ensureRgbFormat($colorData);
        }

        $r = (int) round($colorData['r']);
        $g = (int) round($colorData['g']);
        $b = (int) round($colorData['b']);
        $a = $colorData['a'];

        if ($a < 1.0) {
            $aHex = (int) round($a * 255);

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

        $r /= 255;
        $g /= 255;
        $b /= 255;
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
            's'      => $s * 100,
            'l'      => $l * 100,
            'format' => 'hsl',
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

        $s /= 100;
        $l /= 100;
        $c = (1 - abs(2 * $l - 1)) * $s;
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
            'r'      => ($rgb[0] + $m) * 255,
            'g'      => ($rgb[1] + $m) * 255,
            'b'      => ($rgb[2] + $m) * 255,
            'a'      => 1.0,
            'format' => 'rgb',
        ];

        self::$hslToRgbCache[$key] = $result;

        return $result;
    }

    private function rgbToHwb(float $r, float $g, float $b): array
    {
        $r /= 255;
        $g /= 255;
        $b /= 255;
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
            'w'      => round($min * 100, 5),  // *100 → %
            'bl'     => round((1 - $max) * 100, 5),  // *100 → %
            'format' => 'hwb',
        ];
    }

    private function hwbToRgb(float $h, float $w, float $bl): array  // w, bl: % (0–100)
    {
        $w /= 100.0;  // → fraction 0–1
        $bl /= 100.0;

        $chroma = max(0.0, 1.0 - $w - $bl);

        $hsl = $this->hslToRgb($h, 100.0, 50.0);

        // Clamp HSL
        $hsl['r'] = min(255.0, max(0.0, round($hsl['r'])));
        $hsl['g'] = min(255.0, max(0.0, round($hsl['g'])));
        $hsl['b'] = min(255.0, max(0.0, round($hsl['b'])));

        $r = ($hsl['r'] / 255.0 * $chroma + $w) * 255.0;
        $g = ($hsl['g'] / 255.0 * $chroma + $w) * 255.0;
        $b = ($hsl['b'] / 255.0 * $chroma + $w) * 255.0;

        return [
            'r'      => round($r),
            'g'      => round($g),
            'b'      => round($b),
            'a'      => 1.0,
            'format' => 'rgb',
        ];
    }

    private function linearizeChannel(float $val): float
    {
        return $val > 0.04045 ? pow(($val + 0.055) / 1.055, 2.4) : $val / 12.92;
    }

    private function unlinearizeChannel(float $val): float
    {
        return $val > 0.0031308 ? 1.055 * pow($val, 1 / 2.4) - 0.055 : 12.92 * $val;
    }

    private function rgbToXyz(float $r, float $g, float $b): array
    {
        $r /= 255.0;
        $g /= 255.0;
        $b /= 255.0;

        $r = $this->linearizeChannel($r);
        $g = $this->linearizeChannel($g);
        $b = $this->linearizeChannel($b);

        $x = $r * 0.4124 + $g * 0.3576 + $b * 0.1805;
        $y = $r * 0.2126 + $g * 0.7152 + $b * 0.0722;
        $z = $r * 0.0193 + $g * 0.1192 + $b * 0.9505;

        return [
            'x' => $x * 100.0,
            'y' => $y * 100.0,
            'z' => $z * 100.0,
        ];
    }

    private function xyzToRgb(float $x, float $y, float $z): array
    {
        $x /= 100.0;
        $y /= 100.0;
        $z /= 100.0;

        $r = $x * 3.2406 + $y * -1.5372 + $z * -0.4986;
        $g = $x * -0.9689 + $y * 1.8758 + $z * 0.0415;
        $b = $x * 0.0557 + $y * -0.2040 + $z * 1.0570;

        $r = $this->unlinearizeChannel($r);
        $g = $this->unlinearizeChannel($g);
        $b = $this->unlinearizeChannel($b);

        return [
            'r'      => $this->clamp($r * 255.0, 0.0, 255.0),
            'g'      => $this->clamp($g * 255.0, 0.0, 255.0),
            'b'      => $this->clamp($b * 255.0, 0.0, 255.0),
            'a'      => 1.0,
            'format' => 'rgb',
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

    private function clamp(float $value, float $min, float $max): float
    {
        return min(max($value, $min), $max);
    }
}
