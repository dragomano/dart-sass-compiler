<?php

declare(strict_types=1);

namespace DartSass\Handlers;

use DartSass\Utils\ResultFormatterInterface;

use function array_keys;
use function array_map;
use function count;
use function implode;
use function in_array;

class CssFunctionHandler extends BaseModuleHandler
{
    private const SUPPORTED_FUNCTIONS = [
        'linear-gradient' => true,
    ];

    public function __construct(private readonly ResultFormatterInterface $resultFormatter) {}

    public function canHandle(string $functionName): bool
    {
        return isset(self::SUPPORTED_FUNCTIONS[$functionName]);
    }

    public function handle(string $functionName, array $args): string
    {
        return match ($functionName) {
            'linear-gradient' => $this->handleLinearGradient($args),
            default           => $this->formatCssFunction($functionName, $args),
        };
    }

    public function getSupportedFunctions(): array
    {
        return array_keys(self::SUPPORTED_FUNCTIONS);
    }

    public function getModuleNamespace(): string
    {
        return 'css';
    }

    private function handleLinearGradient(array $args): string
    {
        if (count($args) < 2) {
            return $this->formatCssFunction('linear-gradient', $args);
        }

        $formattedArgs = array_map($this->resultFormatter->format(...), $args);

        if ($formattedArgs[0] === 'to' && in_array($formattedArgs[1], ['bottom', 'right'], true)) {
            $direction  = $formattedArgs[0] . ' ' . $formattedArgs[1];
            $mergedArgs = [$direction];

            for ($i = 2; $i < count($args); $i++) {
                $mergedArgs[] = $this->resultFormatter->format($args[$i]);
            }

            return 'linear-gradient(' . implode(', ', $mergedArgs) . ')';
        }

        return $this->formatCssFunction('linear-gradient', $args);
    }

    private function formatCssFunction(string $functionName, array $args): string
    {
        $formattedArgs = array_map($this->resultFormatter->format(...), $args);

        return $functionName . '(' . implode(', ', $formattedArgs) . ')';
    }
}
