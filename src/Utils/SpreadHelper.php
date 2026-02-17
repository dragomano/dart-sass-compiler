<?php

declare(strict_types=1);

namespace DartSass\Utils;

use DartSass\Parsers\Nodes\ListNode;
use DartSass\Values\SassList;
use DartSass\Values\SassMap;

use function in_array;
use function is_array;
use function is_int;
use function is_string;
use function str_starts_with;
use function substr;

final class SpreadHelper
{
    public static function isSpread(mixed $arg): bool
    {
        return is_array($arg) && isset($arg['type']) && $arg['type'] === 'spread';
    }

    public static function expand(array $args, callable $evaluate): array
    {
        $result = [];

        foreach ($args as $key => $arg) {
            if (self::isSpread($arg)) {
                $spreadValue = $evaluate($arg['value']);

                $result = self::merge($result, $spreadValue, $evaluate);
            } else {
                if (is_string($key) && str_starts_with($key, '$')) {
                    $result[$key] = $arg;
                } else {
                    $result[] = $arg;
                }
            }
        }

        return $result;
    }

    public static function collect(array $args, array $usedKeys): SassList
    {
        [$pos] = self::filter($args, $usedKeys);

        return new SassList($pos);
    }

    public static function collectWithKeywords(array $args, array $usedKeys): SassList|SassMap
    {
        [$pos, $keywords] = self::filter($args, $usedKeys);

        return empty($keywords) ? new SassList($pos) : new SassMap($keywords);
    }

    private static function filter(array $args, array $usedKeys): array
    {
        $pos      = [];
        $keywords = [];

        foreach ($args as $key => $val) {
            if (in_array($key, $usedKeys, true)) {
                continue;
            }

            if (is_int($key)) {
                $pos[] = $val;
            } else {
                $keyStr = str_starts_with($key, '$') ? substr($key, 1) : $key;

                $keywords[$keyStr] = $val;
            }
        }

        return [$pos, $keywords];
    }

    private static function merge(array $result, mixed $spreadValue, callable $evaluate): array
    {
        if ($spreadValue instanceof SassList) {
            foreach ($spreadValue->value as $item) {
                $result[] = $item;
            }
        } elseif ($spreadValue instanceof ListNode) {
            foreach ($spreadValue->values as $item) {
                $result[] = $evaluate($item);
            }
        } elseif (is_array($spreadValue)) {
            foreach ($spreadValue as $item) {
                $result[] = $evaluate($item);
            }
        } else {
            $result[] = $spreadValue;
        }

        return $result;
    }
}
