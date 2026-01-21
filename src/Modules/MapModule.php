<?php

declare(strict_types=1);

namespace DartSass\Modules;

use DartSass\Values\SassList;
use DartSass\Values\SassMap;

use function array_key_exists;
use function array_keys;
use function array_merge;
use function array_pop;
use function array_slice;
use function array_values;
use function count;
use function end;
use function is_array;
use function is_string;
use function json_decode;
use function key;

class MapModule
{
    public function deepMerge(array $args)
    {
        $map1 = $args[0] ?? null;
        $map2 = $args[1] ?? null;

        if (! $this->isMap($map1) || ! $this->isMap($map2)) {
            return $map1 ?: null;
        }

        return new SassMap($this->deepMergeMaps($map1, $map2));
    }

    public function deepRemove(array $args)
    {
        $map  = $this->normalizeMap($args[0] ?? null);
        $keys = array_slice($args, 1);

        if (! $this->isMap($map)) {
            return $args[0] ?? null;
        }

        return new SassMap($this->deepRemoveKeys($map, $keys));
    }

    public function get(array $args)
    {
        [$map, $keys] = $this->parseMapAndKeys($args);

        if (! $this->isMap($map)) {
            return null;
        }

        $map    = $this->normalizeMap($map);
        $result = $this->getNestedValue($map, $keys);

        return $this->wrapResultIfNeeded($result);
    }

    public function hasKey(array $args): bool
    {
        [$map, $keys] = $this->parseMapAndKeys($args);

        if (! $this->isMap($map)) {
            return false;
        }

        $map = $this->normalizeMap($map);

        return $this->hasNestedKey($map, $keys);
    }

    public function keys(array $args): ?SassList
    {
        $map = $this->extractMapFromArgs($args);

        if (! $this->isMap($map)) {
            return null;
        }

        $map  = $this->normalizeMap($map);
        $keys = array_keys($map);

        return new SassList($keys, 'comma', false);
    }

    public function merge(array $args)
    {
        if ($this->isNestedMerge($args)) {
            return $this->handleNestedMerge($args);
        }

        [$map1, $map2] = $this->extractTwoMaps($args);

        if (! $this->isMap($map1) || ! $this->isMap($map2)) {
            return $map1 ?: null;
        }

        $map1   = $this->normalizeMap($map1);
        $map2   = $this->normalizeMap($map2);
        $result = array_merge($map1, $map2);

        return new SassMap($result);
    }

    public function remove(array $args)
    {
        $originalMap = $args[0] ?? null;
        $map         = $this->normalizeMap($originalMap);
        $keys        = array_slice($args, 1);

        if (! $this->isMap($map)) {
            return $originalMap;
        }

        foreach ($keys as $key) {
            unset($map[$key]);
        }

        return new SassMap($map);
    }

    public function set(array $args): SassMap
    {
        $map   = $this->normalizeMap($args[0] ?? null);
        $value = array_pop($args);
        $keys  = array_slice($args, 1);

        if (! $this->isMap($map)) {
            $map = [];
        }

        if (empty($keys)) {
            return new SassMap($map);
        }

        $this->setNestedValue($map, $keys, $value);

        return new SassMap($map);
    }

    public function values(array $args): ?SassList
    {
        $map = $this->extractMapFromArgs($args);

        if (! $this->isMap($map)) {
            return null;
        }

        $map    = $this->normalizeMap($map);
        $values = array_values($map);

        return new SassList($values, 'comma', false);
    }

    private function normalizeMap(mixed $map): mixed
    {
        if ($map instanceof SassList) {
            return $this->convertSassListToArray($map);
        }

        if ($map instanceof SassMap) {
            return $map->value;
        }

        return $map;
    }

    private function parseMapAndKeys(array $args): array
    {
        if ($this->isDirectMap($args)) {
            return [$args, []];
        }

        $map  = $args[0] ?? null;
        $keys = array_slice($args, 1);

        return [$map, $keys];
    }

    private function extractMapFromArgs(array $args): mixed
    {
        if ($this->isDirectMap($args)) {
            return $args;
        }

        return $args[0] ?? null;
    }

    private function extractTwoMaps(array $args): array
    {
        if ($this->isDirectMap($args)) {
            return [$args, []];
        }

        return [$args[0] ?? null, $args[1] ?? null];
    }

    private function isDirectMap(array $args): bool
    {
        return ! empty($args) && is_string(key($args));
    }

    private function isNestedMerge(array $args): bool
    {
        if (count($args) < 3) {
            return false;
        }

        $lastArg       = end($args);
        $secondLastArg = $args[count($args) - 2];

        return $this->isMap($lastArg) && ! $this->isMap($secondLastArg);
    }

    private function handleNestedMerge(array $args): ?SassMap
    {
        $map = $args[0];

        if (! $this->isMap($map)) {
            return null;
        }

        $map     = $this->normalizeMap($map);
        $lastArg = $this->normalizeMap(end($args));
        $keys    = array_slice($args, 1, -1);
        $current = $this->getNestedValue($map, $keys);

        if ($this->isMap($current) && $this->isMap($lastArg)) {
            $current = $this->normalizeMap($current);
            $merged  = array_merge($current, $lastArg);
        } else {
            $merged = $lastArg;
        }

        $this->setNestedValue($map, $keys, $merged);

        return new SassMap($map);
    }

    private function wrapResultIfNeeded(mixed $result): mixed
    {
        if (is_string($result) && $this->isJsonMap($result)) {
            return new SassMap(json_decode($result, true));
        }

        if (is_array($result) && $this->isMap($result)) {
            return new SassMap($result);
        }

        return $result;
    }

    private function isMap(mixed $value): bool
    {
        if ($value instanceof SassMap || $value instanceof SassList) {
            return true;
        }

        if (! is_array($value)) {
            return false;
        }

        if (isset($value['value']) && isset($value['unit'])) {
            return false;
        }

        return true;
    }

    private function isJsonMap(string $str): bool
    {
        $decoded = json_decode($str, true);

        return is_array($decoded) && $this->isMap($decoded);
    }

    private function convertSassListToArray(SassList $list): array
    {
        $arrayValue = [];
        $values = $list->value;

        for ($i = 0; $i < count($values); $i += 3) {
            if (isset($values[$i], $values[$i + 1], $values[$i + 2]) && $values[$i + 1] === ':') {
                $key = $values[$i];
                $val = $values[$i + 2];

                if ($val instanceof SassList) {
                    $val = $this->convertSassListToArray($val);
                }

                $arrayValue[$key] = $val;
            }
        }

        return $arrayValue;
    }

    private function getNestedValue(array $map, array $keys): mixed
    {
        $current = $map;

        foreach ($keys as $key) {
            $current = $this->normalizeMap($current);

            if (is_array($key)) {
                $current = $this->getNestedValue($current, $key);
            } elseif (is_array($current) && array_key_exists($key, $current)) {
                $current = $current[$key];
            } else {
                return null;
            }
        }

        return $this->normalizeMap($current);
    }

    private function hasNestedKey(array $map, array $keys): bool
    {
        $current = $map;

        foreach ($keys as $key) {
            $current = $this->normalizeMap($current);

            if (is_array($key)) {
                if (! $this->hasNestedKey($current, $key)) {
                    return false;
                }
            } elseif (is_array($current) && array_key_exists($key, $current)) {
                $current = $current[$key];
            } else {
                return false;
            }
        }

        return true;
    }

    private function setNestedValue(array &$map, array $keys, mixed $value): void
    {
        $current = &$map;

        foreach ($keys as $i => $key) {
            if ($i === count($keys) - 1) {
                $current[$key] = $value;
            } else {
                if (! isset($current[$key]) || ! is_array($current[$key])) {
                    $current[$key] = [];
                }

                $current = &$current[$key];
            }
        }
    }

    private function deepMergeMaps(array $map1, array $map2): array
    {
        $map1   = $this->normalizeMap($map1);
        $map2   = $this->normalizeMap($map2);
        $result = $map1;

        foreach ($map2 as $key => $value) {
            $value = $this->normalizeMap($value);

            if (isset($result[$key]) && is_array($result[$key]) && is_array($value)) {
                $result[$key] = $this->deepMergeMaps($result[$key], $value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    private function deepRemoveKeys(array $map, array $keys): array
    {
        if (empty($keys)) {
            return $map;
        }

        $lastKey = array_pop($keys);

        if (empty($keys)) {
            unset($map[$lastKey]);

            return $map;
        }

        $current = &$map;
        foreach ($keys as $key) {
            if (! isset($current[$key]) || ! is_array($current[$key])) {
                return $map;
            }

            $current = &$current[$key];
        }

        unset($current[$lastKey]);

        return $map;
    }
}
