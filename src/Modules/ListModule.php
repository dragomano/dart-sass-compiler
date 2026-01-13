<?php

declare(strict_types=1);

namespace DartSass\Modules;

use DartSass\Exceptions\CompilationException;
use DartSass\Parsers\Nodes\ListNode;

use function array_filter;
use function array_map;
use function array_merge;
use function array_slice;
use function count;
use function explode;
use function is_array;
use function is_bool;
use function is_numeric;
use function is_string;
use function min;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function trim;

class ListModule
{
    public function append(array $args): SassList
    {
        $list = $args[0] ?? throw new CompilationException('Missing list for append');
        $val  = $args[1] ?? throw new CompilationException('Missing value for append');

        $separator = $args['$separator'] ?? ($args[2] ?? 'auto');

        $listObj = $this->toSassList($list);
        $valArr  = $this->toArray($val);

        if ($separator === 'auto') {
            $separator = $listObj->separator;
        }

        $newValue = array_merge($listObj->value, $valArr);

        return new SassList($newValue, $separator, $listObj->bracketed);
    }

    public function index(array $args): ?int
    {
        $list  = $args[0] ?? throw new CompilationException('Missing list for index');
        $value = $args[1] ?? throw new CompilationException('Missing value for index');

        $listObj = $this->toSassList($list);

        foreach ($listObj->value as $index => $item) {
            if ($this->valuesEqual($item, $value)) {
                return $index + 1;
            }
        }

        return null;
    }

    public function isBracketed(array $args): bool
    {
        $list = $args[0] ?? throw new CompilationException('Missing list for is-bracketed');

        return $this->toSassList($list)->bracketed;
    }

    public function join(array $args): SassList
    {
        $list1 = $args[0] ?? throw new CompilationException('Missing list1 for join');
        $list2 = $args[1] ?? throw new CompilationException('Missing list2 for join');

        $separator = $args['$separator'] ?? ($args[2] ?? 'auto');
        $bracketed = $args['$bracketed'] ?? ($args[3] ?? 'auto');

        $list1Obj = $this->toSassList($list1);
        $list2Obj = $this->toSassList($list2);

        $separator = $this->resolveSeparator($separator, $list1Obj, $list2Obj);
        $bracketed = $this->resolveBracketed($bracketed, $list1Obj);

        $newValue = array_merge($list1Obj->value, $list2Obj->value);

        return new SassList($newValue, $separator, $bracketed);
    }

    public function length(array $args): int
    {
        $value = $args[0] ?? throw new CompilationException('Missing list for length');
        $value = $this->toArray($value);

        return count($value);
    }

    public function nth(array $args): mixed
    {
        if (count($args) > 2) {
            $indexArg = $args[count($args) - 1];

            $list = array_slice($args, 0, count($args) - 1);
        } else {
            $listArg  = $args[0] ?? throw new CompilationException('Missing list for nth');
            $indexArg = $args[1] ?? throw new CompilationException('Missing index for nth');

            $list = $this->parseListArg($listArg);
        }

        $index = $this->parseIndex($indexArg, count($list));

        if (empty($list) || $index < 1 || $index > count($list)) {
            throw new CompilationException("Index $index out of bounds for list");
        }

        return $list[$index - 1];
    }

    public function separator(array $args): string
    {
        $list = $args[0] ?? throw new CompilationException('Missing list for separator');

        return $this->toSassList($list)->separator;
    }

    public function setNth(array $args): SassList
    {
        if (count($args) > 3) {
            $indexArg = $args[count($args) - 2];
            $value    = $args[count($args) - 1];
            $listArgs = array_slice($args, 0, count($args) - 2);
            $listObj  = $this->toSassList($listArgs);
        } else {
            $list     = $args[0] ?? throw new CompilationException('Missing list for set-nth');
            $indexArg = $args[1] ?? throw new CompilationException('Missing index for set-nth');
            $value    = $args[2] ?? throw new CompilationException('Missing value for set-nth');
            $listObj  = $this->toSassList($list);
        }

        $arr   = $listObj->value;
        $index = $this->parseIndex($indexArg, count($arr));

        if ($index < 1 || $index > count($arr)) {
            throw new CompilationException("Index $index out of bounds");
        }

        $arr[$index - 1] = $value;

        return new SassList($arr, $listObj->separator, $listObj->bracketed);
    }

    public function slash(array $args): SassList
    {
        if (count($args) < 2) {
            throw new CompilationException('slash requires at least two elements');
        }

        return new SassList($args, 'slash', false);
    }

    public function zip(array $args): array
    {
        if (empty($args)) {
            return [];
        }

        $sassLists = array_map($this->toSassList(...), $args);
        $values    = array_map(fn($sl): array => $sl->value, $sassLists);
        $minLength = min(array_map(count(...), $values));
        $result    = [];

        for ($i = 0; $i < $minLength; $i++) {
            $row = array_map(fn($arr) => $arr[$i] ?? null, $values);
            $result[] = new SassList($row, 'space', false);
        }

        return $result;
    }

    private function resolveSeparator(string $separator, SassList $list1, SassList $list2): string
    {
        if ($separator !== 'auto') {
            return $separator;
        }

        return $list1->separator === $list2->separator ? $list1->separator : 'space';
    }

    private function resolveBracketed(mixed $bracketed, SassList $list1): bool
    {
        if ($bracketed === 'auto') {
            return $list1->bracketed;
        }

        return is_bool($bracketed) ? $bracketed : $list1->bracketed;
    }

    private function parseListArg(mixed $listArg): array
    {
        // Handle wrapped single-element array
        if (is_array($listArg) && count($listArg) === 1 && isset($listArg[0])) {
            return $this->parseWrappedValue($listArg[0]);
        }

        if ($listArg instanceof SassList) {
            return $listArg->value;
        }

        if ($listArg instanceof ListNode) {
            return $listArg->values;
        }

        if (is_array($listArg)) {
            return (isset($listArg['value']) || isset($listArg['unit']))
                ? [$listArg]
                : $listArg;
        }

        return is_string($listArg) ? $this->parseStringToList($listArg) : [$listArg];
    }

    private function parseWrappedValue(mixed $innerValue): array
    {
        if ($innerValue instanceof SassList) {
            return $innerValue->value;
        }

        if ($innerValue instanceof ListNode) {
            return $innerValue->values;
        }

        if (is_string($innerValue)) {
            return $this->parseStringToList($innerValue);
        }

        return [$innerValue];
    }

    private function parseStringToList(string $value): array
    {
        $separator = str_contains($value, ',') ? ',' : ' ';

        $list = array_map(trim(...), explode($separator, $value));

        return array_filter($list, fn($item): bool => $item !== '');
    }

    private function parseIndex(mixed $indexArg, int $listLength): int
    {
        $index = is_numeric($indexArg)
            ? (int) $indexArg
            : (is_array($indexArg) && isset($indexArg['value']) ? (int) $indexArg['value'] : 1);

        // Handle negative indices
        if ($index < 0) {
            $index = $listLength + $index + 1;
        }

        return $index;
    }

    private function toArray(mixed $value): array
    {
        if ($value instanceof ListNode) {
            return $value->values;
        }

        if ($value instanceof SassList) {
            return $value->value;
        }

        if (is_array($value) && isset($value['value'])) {
            return [$value];
        }

        if (is_array($value)) {
            return $value;
        }

        return is_string($value) ? $this->parseStringToList($value) : [$value];
    }

    private function toSassList(mixed $value): SassList
    {
        if ($value instanceof SassList) {
            return $value;
        }

        if ($value instanceof ListNode) {
            return new SassList($value->values, $value->separator, $value->bracketed);
        }

        $separator = 'space';
        $bracketed = false;

        if (is_string($value)) {
            $trimmed = trim($value);

            // Check for bracketed lists
            if (str_starts_with($trimmed, '[') && str_ends_with($trimmed, ']')) {
                $bracketed = true;
                $value = trim($trimmed, '[]');
            }

            // Check for comma-separated lists
            if (str_contains($value, ',')) {
                $separator = 'comma';
            }
        }

        $valArr = $this->toArray($value);

        return new SassList($valArr, $separator, $bracketed);
    }

    private function valuesEqual(mixed $item1, mixed $item2): bool
    {
        return $this->formatValueForComparison($item1) === $this->formatValueForComparison($item2);
    }

    private function formatValueForComparison(mixed $item): string
    {
        if (is_array($item) && isset($item['value'])) {
            return (string) $item['value'];
        }

        return (string) $item;
    }
}
