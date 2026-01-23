<?php

declare(strict_types=1);

namespace DartSass\Modules;

use DartSass\Exceptions\CompilationException;
use DartSass\Parsers\Nodes\ListNode;
use DartSass\Utils\ValueComparator;
use DartSass\Values\SassList;
use DartSass\Values\SassMap;
use DartSass\Values\SassNumber;

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

class ListModule extends AbstractModule
{
    public function append(array $args): SassList
    {
        $this->validateArgs($args, 2, 'append', true);

        $list = $args[0];
        $val  = $args[1];

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
        [$list, $value] = $this->validateArgs($args, 2, 'index');

        $listObj = $this->toSassList($list);

        foreach ($listObj->value as $index => $item) {
            if (ValueComparator::equals($item, $value)) {
                return $index + 1;
            }
        }

        return null;
    }

    public function isBracketed(array $args): bool
    {
        [$list] = $this->validateArgs($args, 1, 'is-bracketed');

        return $this->toSassList($list)->bracketed;
    }

    public function join(array $args): SassList
    {
        $this->validateArgs($args, 2, 'join', true);

        $list1 = $args[0];
        $list2 = $args[1];

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
        [$value] = $this->validateArgs($args, 1, 'length');

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
        [$list] = $this->validateArgs($args, 1, 'separator');

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
        $this->validateArgs($args, 2, 'slash', true);

        return new SassList($args, 'slash', false);
    }

    public function zip(array $args): SassList
    {
        $this->validateArgs($args, 0, 'zip', true);

        if (empty($args)) {
            return new SassList([], 'comma', false);
        }

        $sassLists = array_map($this->toSassList(...), $args);
        $values    = array_map(fn(SassList $sl): array => $sl->value, $sassLists);
        $minLength = min(array_map(count(...), $values));
        $result    = [];

        for ($i = 0; $i < $minLength; $i++) {
            $row = array_map(fn($arr) => $arr[$i] ?? null, $values);
            $result[] = new SassList($row, 'space', false);
        }

        return new SassList($result, 'comma', false);
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
        $index = 1;

        if (is_numeric($indexArg)) {
            $index = (int) $indexArg;
        } elseif ($indexArg instanceof SassNumber) {
            $index = (int) $indexArg->getValue();
        } elseif (is_array($indexArg) && isset($indexArg['value'])) {
            $index = (int) $indexArg['value'];
        }

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

        if ($value instanceof SassMap) {
            return array_keys($value->value);
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

            if (str_starts_with($trimmed, '[') && str_ends_with($trimmed, ']')) {
                $bracketed = true;
                $value = trim($trimmed, '[]');
            }

            if (str_contains($value, ',')) {
                $separator = 'comma';
            }
        }

        $valArr = $this->toArray($value);

        return new SassList($valArr, $separator, $bracketed);
    }
}
