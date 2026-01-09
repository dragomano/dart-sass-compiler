<?php

declare(strict_types=1);

namespace DartSass\Handlers\ModuleHandlers;

use DartSass\Handlers\SassModule;
use DartSass\Utils\ResultFormatterInterface;

use function array_map;
use function count;
use function implode;
use function in_array;
use function preg_match;

class LinearGradientFunctionHandler extends BaseModuleHandler
{
    protected const GLOBAL_FUNCTIONS = ['linear-gradient'];

    public function __construct(private readonly ResultFormatterInterface $resultFormatter) {}

    public function handle(string $functionName, array $args): string
    {
        $formattedArgs = array_map($this->resultFormatter->format(...), $args);

        if (count($args) >= 2) {
            $formattedArgs = $this->reconstructArguments($formattedArgs);
        }

        return 'linear-gradient(' . implode(', ', $formattedArgs) . ')';
    }

    public function getModuleNamespace(): SassModule
    {
        return SassModule::CSS;
    }

    private function reconstructArguments(array $args): array
    {
        $reconstructed = [];
        $count = count($args);
        $i = 0;

        while ($i < $count) {
            $current = $args[$i];

            if ($current === 'to' && isset($args[$i + 1])) {
                $direction = $current . ' ' . $args[$i + 1];
                $skip = 1;

                if (isset($args[$i + 2]) && $this->isDirectionKeyword($args[$i + 2])) {
                    $direction .= ' ' . $args[$i + 2];
                    $skip = 2;
                }

                $reconstructed[] = $direction;
                $i += $skip + 1;

                continue;
            }

            if (isset($args[$i + 1]) && $this->isValidNumberUnitPair($current, $args[$i + 1])) {
                $reconstructed[] = $current . $args[$i + 1];
                $i += 2;

                continue;
            }

            if (isset($args[$i + 1]) && $this->isValidColorStopPair($current, $args[$i + 1])) {
                $reconstructed[] = $current . ' ' . $args[$i + 1];
                $i += 2;

                continue;
            }

            $reconstructed[] = $current;
            $i++;
        }

        return $reconstructed;
    }

    private function isDirectionKeyword(string $value): bool
    {
        return in_array($value, ['top', 'bottom', 'left', 'right', 'center'], true);
    }

    private function isValidNumberUnitPair(string $number, string $unit): bool
    {
        return preg_match('/^-?(\d*\.)?\d+$/', $number) === 1
            && in_array($unit, ['deg', 'grad', 'rad', 'turn'], true);
    }

    private function isValidColorStopPair(string $color, string $position): bool
    {
        return $this->isColorLike($color) && $this->isPositionLike($position);
    }

    private function isColorLike(string $value): bool
    {
        return preg_match('/^(#[0-9a-fA-F]{3,8}|[a-zA-Z]+|[a-z]+\(.*\))$/', $value) === 1;
    }

    private function isPositionLike(string $value): bool
    {
        return $value === '0' || preg_match('/^-?(\d*\.)?\d+([a-zA-Z%]+)$/', $value) === 1;
    }
}
