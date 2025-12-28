<?php

declare(strict_types=1);

namespace DartSass\Modules;

use function abs;
use function atan2;
use function cos;
use function fmod;
use function max;
use function min;
use function round;
use function sin;
use function sqrt;

use const M_PI;

enum ColorConverter
{
    case RGB;
    case HSL;
    case HWB;
    case LAB;
    case LCH;
    case OKLCH;
    case XYZ;

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

    public function toHsl(float ...$values): array
    {
        return match ($this) {
            self::HSL   => ['h' => $values[0], 's' => $values[1], 'l' => $values[2]],
            self::HWB   => self::hwbToHsl($values[0], $values[1], $values[2]),
            self::LAB   => self::labToHsl($values[0], $values[1], $values[2]),
            self::LCH   => self::lchToHsl($values[0], $values[1], $values[2]),
            self::OKLCH => self::oklchToHsl($values[0], $values[1], $values[2]),
            self::RGB   => self::rgbToHsl($values[0], $values[1], $values[2]),
            self::XYZ   => self::xyzToHsl($values[0], $values[1], $values[2]),
        };
    }

    public function toHwb(float ...$values): array
    {
        return match ($this) {
            self::HSL   => self::hslToHwb($values[0], $values[1], $values[2]),
            self::HWB   => ['h' => $values[0],'w' => $values[1], 'bl' => $values[2]],
            self::LAB   => self::labToHwb($values[0], $values[1], $values[2]),
            self::LCH   => self::lchToHwb($values[0], $values[1], $values[2]),
            self::OKLCH => self::oklchToHwb($values[0], $values[1], $values[2]),
            self::RGB   => self::rgbToHwb($values[0], $values[1], $values[2]),
            self::XYZ   => self::xyzToHwb($values[0], $values[1], $values[2]),
        };
    }

    public function toLab(float ...$values): array
    {
        return match ($this) {
            self::HSL   => self::hslToLab($values[0], $values[1], $values[2]),
            self::HWB   => self::hwbToLab($values[0], $values[1], $values[2]),
            self::LAB   => ['l' => $values[0], 'a' => $values[1], 'b' => $values[2]],
            self::LCH   => self::lchToLab($values[0], $values[1], $values[2]),
            self::OKLCH => self::oklchToLab($values[0], $values[1], $values[2]),
            self::RGB   => self::rgbToLab($values[0], $values[1], $values[2]),
            self::XYZ   => self::xyzToLab($values[0], $values[1], $values[2]),
        };
    }

    public function toLch(float ...$values): array
    {
        return match ($this) {
            self::HSL   => self::hslToLch($values[0], $values[1], $values[2]),
            self::HWB   => self::hwbToLch($values[0], $values[1], $values[2]),
            self::LAB   => self::labToLch($values[0], $values[1], $values[2]),
            self::LCH   => ['l' => $values[0], 'c' => $values[1], 'h' => $values[2]],
            self::OKLCH => self::oklchToLch($values[0], $values[1], $values[2]),
            self::RGB   => self::rgbToLch($values[0], $values[1], $values[2]),
            self::XYZ   => self::xyzToLch($values[0], $values[1], $values[2]),
        };
    }

    public function toOklch(float ...$values): array
    {
        return match ($this) {
            self::HSL   => self::hslToOklch($values[0], $values[1], $values[2]),
            self::HWB   => self::hwbToOklch($values[0], $values[1], $values[2]),
            self::LAB   => self::labToOklch($values[0], $values[1], $values[2]),
            self::LCH   => self::lchToOklch($values[0], $values[1], $values[2]),
            self::OKLCH => ['l' => $values[0], 'c' => $values[1], 'h' => $values[2]],
            self::RGB   => self::rgbToOklch($values[0], $values[1], $values[2]),
            self::XYZ   => self::xyzToOklch($values[0], $values[1], $values[2]),
        };
    }

    public function toRgb(float ...$values): array
    {
        return match ($this) {
            self::HSL   => self::hslToRgb($values[0], $values[1], $values[2]),
            self::HWB   => self::hwbToRgb($values[0], $values[1], $values[2]),
            self::LAB   => self::labToRgb($values[0], $values[1], $values[2]),
            self::LCH   => self::lchToRgb($values[0], $values[1], $values[2]),
            self::OKLCH => self::oklchToRgb($values[0], $values[1], $values[2]),
            self::RGB   => ['r' => $values[0], 'g' => $values[1], 'b' => $values[2]],
            self::XYZ   => self::xyzToRgb($values[0], $values[1], $values[2]),
        };
    }

    public function toXyz(float ...$values): array
    {
        return match ($this) {
            self::HSL   => self::hslToXyz($values[0], $values[1], $values[2]),
            self::HWB   => self::hwbToXyz($values[0], $values[1], $values[2]),
            self::LAB   => self::labToXyz($values[0], $values[1], $values[2]),
            self::LCH   => self::lchToXyz($values[0], $values[1], $values[2]),
            self::OKLCH => self::oklchToXyz($values[0], $values[1], $values[2]),
            self::RGB   => self::rgbToXyz($values[0], $values[1], $values[2]),
            self::XYZ   => ['x' => $values[0], 'y' => $values[1], 'z' => $values[2]],
        };
    }

    private static function hwbToHsl(float $h, float $w, float $bl): array
    {
        $rgb = self::hwbToRgb($h, $w, $bl);

        return self::rgbToHsl($rgb['r'], $rgb['g'], $rgb['b']);
    }

    private static function labToHsl(float $l, float $a, float $b): array
    {
        $lch = self::labToLch($l, $a, $b);

        return self::lchToHsl($lch['l'], $lch['c'], $lch['h']);
    }

    private static function lchToHsl(float $l, float $c, float $h): array
    {
        $rgb = self::lchToRgb($l, $c, $h);

        return self::rgbToHsl($rgb['r'], $rgb['g'], $rgb['b']);
    }

    private static function oklchToHsl(float $l, float $c, float $h): array
    {
        $rgb = self::oklchToRgb($l, $c, $h);

        return self::rgbToHsl($rgb['r'], $rgb['g'], $rgb['b']);
    }

    private static function rgbToHsl(float $r, float $g, float $b): array
    {
        $r /= self::RGB_MAX;
        $g /= self::RGB_MAX;
        $b /= self::RGB_MAX;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l   = ($max + $min) / 2;

        $s = 0;
        $h = 0;

        if ($max !== $min) {
            $d = $max - $min;
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);

            $h = match (true) {
                $max === $r => (($g - $b) / $d) + ($g < $b ? 6 : 0),
                $max === $g => (($b - $r) / $d) + 2,
                $max === $b => (($r - $g) / $d) + 4,
            } * 60;
        }

        return [
            'h' => $h,
            's' => $s * self::PERCENT_MAX,
            'l' => $l * self::PERCENT_MAX,
        ];
    }

    private static function xyzToHsl(float $l, float $c, float $h): array
    {
        $rgb = self::xyzToRgb($l, $c, $h);

        return self::rgbToHsl($rgb['r'], $rgb['g'], $rgb['b']);
    }

    private static function hslToHwb(float $h, float $s, float $l): array
    {
        $rgb = self::hslToRgb($h, $s, $l);

        return self::rgbToHwb($rgb['r'], $rgb['g'], $rgb['b']);
    }

    private static function labToHwb(float $l, float $a, float $b): array
    {
        $lch = self::labToLch($l, $a, $b);

        return self::lchToHwb($lch['l'], $lch['c'], $lch['h']);
    }

    private static function lchToHwb(float $l, float $c, float $h): array
    {
        $rgb = self::lchToRgb($l, $c, $h);

        return self::rgbToHwb($rgb['r'], $rgb['g'], $rgb['b']);
    }

    private static function oklchToHwb(float $l, float $c, float $h): array
    {
        $rgb = self::oklchToRgb($l, $c, $h);

        return self::rgbToHwb($rgb['r'], $rgb['g'], $rgb['b']);
    }

    private static function rgbToHwb(float $r, float $g, float $b): array
    {
        $r /= self::RGB_MAX;
        $g /= self::RGB_MAX;
        $b /= self::RGB_MAX;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);

        $h = 0;

        if ($max !== $min) {
            $h = match (true) {
                $max === $r => (($g - $b) / ($max - $min)) + ($g < $b ? 6 : 0),
                $max === $g => (($b - $r) / ($max - $min)) + 2,
                $max === $b => (($r - $g) / ($max - $min)) + 4,
            } * 60;
        }

        return [
            'h'  => round($h, 5),
            'w'  => round($min * self::PERCENT_MAX, 5),
            'bl' => round((1 - $max) * self::PERCENT_MAX, 5),
        ];
    }

    private static function xyzToHwb(float $l, float $c, float $h): array
    {
        $rgb = self::xyzToRgb($l, $c, $h);

        return self::rgbToHwb($rgb['r'], $rgb['g'], $rgb['b']);
    }

    private static function hslToLab(float $h, float $s, float $l): array
    {
        $lch = self::hslToLch($h, $s, $l);

        return self::lchToLab($lch['l'], $lch['c'], $lch['h']);
    }

    private static function hwbToLab(float $h, float $w, float $bl): array
    {
        $lch = self::hwbToLch($h, $w, $bl);

        return self::lchToLab($lch['l'], $lch['c'], $lch['h']);
    }

    private static function lchToLab(float $l, float $c, float $h): array
    {
        $hRad = $h * M_PI / self::HUE_SHIFT;
        $a    = $c * cos($hRad);
        $b    = $c * sin($hRad);

        return ['lab_l' => $l, 'lab_a' => $a, 'lab_b' => $b];
    }

    private static function oklchToLab(float $l, float $c, float $h): array
    {
        $lch = self::oklchToLch($l, $c, $h);

        return self::lchToLab($lch['l'], $lch['c'], $lch['h']);
    }

    private static function rgbToLab(float $r, float $g, float $b): array
    {
        $lch = self::rgbToLch($r, $g, $b);

        return self::lchToLab($lch['l'], $lch['c'], $lch['h']);
    }

    private static function hslToLch(float $h, float $s, float $l): array
    {
        $rgb = self::hslToRgb($h, $s, $l);

        return self::rgbToLch($rgb['r'], $rgb['g'], $rgb['b']);
    }

    private static function hwbToLch(float $h, float $w, float $bl): array
    {
        $rgb = self::hwbToRgb($h, $w, $bl);

        return self::rgbToLch($rgb['r'], $rgb['g'], $rgb['b']);
    }

    private static function labToLch(float $l, float $a, float $b): array
    {
        $c = sqrt($a * $a + $b * $b);
        $h = atan2($b, $a) * self::HUE_SHIFT / M_PI;

        if ($h < 0) {
            $h += self::HUE_MAX;
        }

        return ['l' => $l, 'c' => $c, 'h' => $h];
    }

    private static function oklchToLch(float $l, float $c, float $h): array
    {
        $rgb = self::oklchToRgb($l, $c, $h);

        return self::rgbToLch($rgb['r'], $rgb['g'], $rgb['b']);
    }

    private static function rgbToLch(float $r, float $g, float $b): array
    {
        $xyz = self::rgbToXyz($r, $g, $b);
        $lab = self::xyzToLab($xyz['x'], $xyz['y'], $xyz['z']);

        return self::labToLch($lab['lab_l'], $lab['lab_a'], $lab['lab_b']);
    }

    private static function xyzToLch(float $l, float $c, float $h): array
    {
        $rgb = self::xyzToRgb($l, $c, $h);

        return self::rgbToLch($rgb['r'], $rgb['g'], $rgb['b']);
    }

    private static function hslToOklch(float $h, float $s, float $l): array
    {
        $rgb = self::hslToRgb($h, $s, $l);

        return self::rgbToOklch($rgb['r'], $rgb['g'], $rgb['b']);
    }

    private static function hwbToOklch(float $h, float $w, float $bl): array
    {
        $rgb = self::hwbToRgb($h, $w, $bl);

        return self::rgbToOklch($rgb['r'], $rgb['g'], $rgb['b']);
    }

    private static function labToOklch(float $l, float $a, float $b): array
    {
        $lch = self::labToLch($l, $a, $b);

        return self::lchToOklch($lch['l'], $lch['c'], $lch['h']);
    }

    private static function lchToOklch(float $l, float $c, float $h): array
    {
        $rgb = self::lchToRgb($l, $c, $h);

        return self::rgbToOklch($rgb['r'], $rgb['g'], $rgb['b']);
    }

    private static function rgbToOklch(float $r, float $g, float $b): array
    {
        $r /= self::RGB_MAX;
        $g /= self::RGB_MAX;
        $b /= self::RGB_MAX;

        $r = self::linearizeChannel($r);
        $g = self::linearizeChannel($g);
        $b = self::linearizeChannel($b);

        $l = 0.4122214708 * $r + 0.5363325363 * $g + 0.0514459929 * $b;
        $m = 0.2119034982 * $r + 0.6806995451 * $g + 0.1073969566 * $b;
        $s = 0.0883024619 * $r + 0.2817188376 * $g + 0.6299787005 * $b;

        $l **= 1 / 3;
        $m **= 1 / 3;
        $s **= 1 / 3;

        $L = 0.2104542553 * $l + 0.7936177850 * $m - 0.0040720468 * $s;
        $a = 1.9779984951 * $l - 2.4285922050 * $m + 0.4505937099 * $s;
        $b = 0.0259040371 * $l + 0.7827717662 * $m - 0.8086757660 * $s;

        $C = sqrt($a * $a + $b * $b);
        $H = atan2($b, $a) * self::HUE_SHIFT / M_PI;

        if ($H < 0) {
            $H += self::HUE_MAX;
        }

        return [
            'l' => $L * self::PERCENT_MAX,
            'c' => $C,
            'h' => $H,
        ];
    }

    private static function xyzToOklch(float $l, float $c, float $h): array
    {
        $rgb = self::xyzToRgb($l, $c, $h);

        return self::rgbToOklch($rgb['r'], $rgb['g'], $rgb['b']);
    }

    private static function hslToRgb(float $h, float $s, float $l): array
    {
        $s /= self::PERCENT_MAX;
        $l /= self::PERCENT_MAX;

        $c  = (1 - abs(2 * $l - 1)) * $s;
        $hp = $h / 60;
        $x  = $c * (1 - abs(fmod($hp, 2) - 1));
        $m  = $l - $c / 2;

        $rgb = match (true) {
            $hp >= 0 && $hp < 1 => [$c, $x, 0],
            $hp < 2             => [$x, $c, 0],
            $hp < 3             => [0, $c, $x],
            $hp < 4             => [0, $x, $c],
            $hp < 5             => [$x, 0, $c],
            default             => [$c, 0, $x],
        };

        return [
            'r' => ($rgb[0] + $m) * self::RGB_MAX,
            'g' => ($rgb[1] + $m) * self::RGB_MAX,
            'b' => ($rgb[2] + $m) * self::RGB_MAX,
        ];
    }

    private static function hwbToRgb(float $h, float $w, float $bl): array
    {
        $w  /= self::PERCENT_MAX;
        $bl /= self::PERCENT_MAX;

        $chroma = max(self::ALPHA_MIN, self::ALPHA_MAX - $w - $bl);

        $hsl = self::hslToRgb($h, self::PERCENT_MAX, 50.0);

        $hsl['r'] = min(self::RGB_MAX, max(self::ALPHA_MIN, round($hsl['r'])));
        $hsl['g'] = min(self::RGB_MAX, max(self::ALPHA_MIN, round($hsl['g'])));
        $hsl['b'] = min(self::RGB_MAX, max(self::ALPHA_MIN, round($hsl['b'])));

        $r = ($hsl['r'] / self::RGB_MAX) * $chroma + $w;
        $g = ($hsl['g'] / self::RGB_MAX) * $chroma + $w;
        $b = ($hsl['b'] / self::RGB_MAX) * $chroma + $w;

        return [
            'r' => round($r * self::RGB_MAX),
            'g' => round($g * self::RGB_MAX),
            'b' => round($b * self::RGB_MAX),
        ];
    }

    private static function labToRgb(float $l, float $a, float $b): array
    {
        $lch = self::labToLch($l, $a, $b);

        return self::lchToRgb($lch['l'], $lch['c'], $lch['h']);
    }

    private static function lchToRgb(float $l, float $c, float $h): array
    {
        $lab = self::lchToLab($l, $c, $h);
        $xyz = self::labToXyz($lab['lab_l'], $lab['lab_a'], $lab['lab_b']);

        return self::xyzToRgb($xyz['x'], $xyz['y'], $xyz['z']);
    }

    private static function oklchToRgb(float $l, float $c, float $h): array
    {
        $l /= self::PERCENT_MAX;

        $hueRadians = $h * M_PI / self::HUE_SHIFT;

        $labA = $c * cos($hueRadians);
        $labB = $c * sin($hueRadians);

        $lmsL = $l + 0.3963377776 * $labA + 0.2158037573 * $labB;
        $lmsM = $l - 0.1055613458 * $labA - 0.0638541728 * $labB;
        $lmsS = $l - 0.0894841775 * $labA - 1.2914855480 * $labB;

        $lmsL **= 3;
        $lmsM **= 3;
        $lmsS **= 3;

        $linearR = 4.0767416621 * $lmsL - 3.3077115913 * $lmsM + 0.2309699292 * $lmsS;
        $linearG = -1.2684380046 * $lmsL + 2.6097574011 * $lmsM - 0.3413193965 * $lmsS;
        $linearB = -0.0041960863 * $lmsL - 0.7034186147 * $lmsM + 1.7076147010 * $lmsS;

        $r = self::unLinearizeChannel($linearR);
        $g = self::unLinearizeChannel($linearG);
        $b = self::unLinearizeChannel($linearB);

        return [
            'r' => self::clamp($r * self::RGB_MAX, 0),
            'g' => self::clamp($g * self::RGB_MAX, 0),
            'b' => self::clamp($b * self::RGB_MAX, 0),
        ];
    }

    private static function xyzToRgb(float $x, float $y, float $z): array
    {
        $x /= self::PERCENT_MAX;
        $y /= self::PERCENT_MAX;
        $z /= self::PERCENT_MAX;

        $r = $x * 3.2406 + $y * -1.5372 + $z * -0.4986;
        $g = $x * -0.9689 + $y * 1.8758 + $z * 0.0415;
        $b = $x * 0.0557 + $y * -0.2040 + $z * 1.0570;

        $r = self::unLinearizeChannel($r);
        $g = self::unLinearizeChannel($g);
        $b = self::unLinearizeChannel($b);

        return [
            'r' => self::clamp($r * self::RGB_MAX, self::ALPHA_MIN),
            'g' => self::clamp($g * self::RGB_MAX, self::ALPHA_MIN),
            'b' => self::clamp($b * self::RGB_MAX, self::ALPHA_MIN),
        ];
    }

    private static function hslToXyz(float $h, float $s, float $l): array
    {
        $rgb = self::hslToRgb($h, $s, $l);

        return self::rgbToXyz($rgb['r'], $rgb['g'], $rgb['b']);
    }

    private static function hwbToXyz(float $h, float $w, float $bl): array
    {
        $rgb = self::hwbToRgb($h, $w, $bl);

        return self::rgbToXyz($rgb['r'], $rgb['g'], $rgb['b']);
    }

    private static function labToXyz(float $l, float $a, float $b): array
    {
        $fy = ($l + 16) / 116;
        $fx = $a / 500 + $fy;
        $fz = $fy - $b / 200;

        $x = self::labInverseFunction($fx) * self::XYZ_REF_X;
        $y = self::labInverseFunction($fy) * self::XYZ_REF_Y;
        $z = self::labInverseFunction($fz) * self::XYZ_REF_Z;

        return ['x' => $x, 'y' => $y, 'z' => $z];
    }

    private static function lchToXyz(float $l, float $c, float $h): array
    {
        $lab = self::lchToLab($l, $c, $h);

        return self::labToXyz($lab['lab_l'], $lab['lab_a'], $lab['lab_b']);
    }

    private static function oklchToXyz(float $l, float $c, float $h): array
    {
        $rgb = self::oklchToRgb($l, $c, $h);

        return self::rgbToXyz($rgb['r'], $rgb['g'], $rgb['b']);
    }

    private static function rgbToXyz(float $r, float $g, float $b): array
    {
        $r /= self::RGB_MAX;
        $g /= self::RGB_MAX;
        $b /= self::RGB_MAX;

        $r = self::linearizeChannel($r);
        $g = self::linearizeChannel($g);
        $b = self::linearizeChannel($b);

        $x = $r * 0.4124 + $g * 0.3576 + $b * 0.1805;
        $y = $r * 0.2126 + $g * 0.7152 + $b * 0.0722;
        $z = $r * 0.0193 + $g * 0.1192 + $b * 0.9505;

        return [
            'x' => $x * self::PERCENT_MAX,
            'y' => $y * self::PERCENT_MAX,
            'z' => $z * self::PERCENT_MAX,
        ];
    }

    private static function xyzToLab(float $x, float $y, float $z): array
    {
        $x /= self::XYZ_REF_X;
        $y /= self::XYZ_REF_Y;
        $z /= self::XYZ_REF_Z;

        $x = self::labFunction($x);
        $y = self::labFunction($y);
        $z = self::labFunction($z);

        $l = 116 * $y - 16;
        $a = 500 * ($x - $y);
        $b = 200 * ($y - $z);

        return ['lab_l' => $l, 'lab_a' => $a, 'lab_b' => $b];
    }

    private static function linearizeChannel(float $val): float
    {
        return $val <= 0.04045 ? $val / 12.92 : (($val + 0.055) / 1.055) ** 2.4;
    }

    private static function unLinearizeChannel(float $val): float
    {
        return $val <= 0.0031308 ? 12.92 * $val : 1.055 * ($val ** (1 / 2.4)) - 0.055;
    }

    private static function labFunction(float $t): float
    {
        if ($t > self::LAB_EPSILON) {
            return $t ** (1 / 3);
        }

        return (self::LAB_KAPPA * $t + 16) / 116;
    }

    private static function labInverseFunction(float $t): float
    {
        $t3 = $t * $t * $t;

        if ($t3 > self::LAB_EPSILON) {
            return $t3;
        }

        return (116 * $t - 16) / self::LAB_KAPPA;
    }

    private static function clamp(float $value, float $min): float
    {
        return min(max($value, $min), self::RGB_MAX);
    }
}
