<?php

declare(strict_types=1);

namespace DartSass\Modules;

trait LegacyColorFunctions
{
    public function adjustHue(string $color, float $amount): string
    {
        return $this->adjust($color, ['$hue' => $amount]);
    }

    public function alpha(string $color): string
    {
        return $this->channel($color, 'alpha');
    }

    public function blackness(string $color): string
    {
        return $this->channel($color, 'blackness');
    }

    public function blue(string $color): string
    {
        return $this->channel($color, 'blue');
    }

    public function darken(string $color, float $amount): string
    {
        return $this->lighten($color, -$amount);
    }

    public function desaturate(string $color, float $amount): string
    {
        return $this->saturate($color, -$amount);
    }

    public function green(string $color): string
    {
        return $this->channel($color, 'green');
    }

    public function hue(string $color): string
    {
        return $this->channel($color, 'hue');
    }

    public function lighten(string $color, float $amount): string
    {
        return $this->adjust($color, ['$lightness' => $amount]);
    }

    public function lightness(string $color): string
    {
        return $this->channel($color, 'lightness');
    }

    public function opacify(string $color, float $amount): string
    {
        return $this->adjust($color, ['$alpha' => $amount]);
    }

    public function opacity(string $color): string
    {
        return $this->alpha($color);
    }

    public function fadeIn(string $color, float $amount): string
    {
        return $this->opacify($color, $amount);
    }

    public function fadeOut(string $color, float $amount): string
    {
        return $this->transparentize($color, $amount);
    }

    public function red(string $color): string
    {
        return $this->channel($color, 'red');
    }

    public function saturate(string $color, float $amount): string
    {
        return $this->adjust($color, ['$saturation' => $amount]);
    }

    public function saturation(string $color): string
    {
        return $this->channel($color, 'saturation');
    }

    public function transparentize(string $color, float $amount): string
    {
        return $this->opacify($color, -$amount);
    }

    public function whiteness(string $color): string
    {
        return $this->channel($color, 'whiteness');
    }
}
