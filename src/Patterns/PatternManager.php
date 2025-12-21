<?php

declare(strict_types=1);

namespace DartSass\Patterns;

use function array_column;
use function implode;

class PatternManager
{
    private static array $cachedPatterns = [];

    private static array $cachedRegexes = [];

    public static function getPatterns(string $type = 'scss'): array
    {
        if (! isset(self::$cachedPatterns[$type])) {
            $patterns = $type === 'scss' ? ScssTokenPattern::cases() : SassTokenPattern::cases();
            self::$cachedPatterns[$type] = array_column($patterns, 'value', 'name');
        }

        return self::$cachedPatterns[$type];
    }

    public static function buildRegexFromPatterns(string $type = 'scss'): string
    {
        if (! isset(self::$cachedRegexes[$type])) {
            $patterns = self::getPatterns($type);
            self::$cachedRegexes[$type] = '/(' . implode('|', $patterns) . ')/ms';
        }

        return self::$cachedRegexes[$type];
    }
}
