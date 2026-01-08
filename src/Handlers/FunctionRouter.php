<?php

declare(strict_types=1);

namespace DartSass\Handlers;

use DartSass\Exceptions\CompilationException;
use DartSass\Utils\ResultFormatterInterface;
use Exception;

use function array_map;
use function implode;
use function in_array;
use function is_bool;
use function str_contains;
use function strrchr;
use function substr;

readonly class FunctionRouter
{
    public function __construct(
        private ModuleRegistry           $registry,
        private ResultFormatterInterface $resultFormatter
    ) {}

    public function route(string $functionName, array $args): mixed
    {
        $shortName = str_contains($functionName, '.')
            ? substr(strrchr($functionName, '.'), 1)
            : $functionName;

        // First try the full function name (for namespaced functions)
        $handler = $this->registry->getHandler($functionName);

        // If not found, and it's namespaced, try the short name
        if ($handler === null && str_contains($functionName, '.')) {
            $handler = $this->registry->getHandler($shortName);
        }

        if ($handler === null) {
            return $this->handleUnknownFunction($functionName, $args);
        }

        $requiresRawResult = $handler instanceof LazyEvaluationHandlerInterface
            && $handler->requiresRawResult($shortName);

        try {
            $result = $handler->handle($shortName, $args);

            if ($requiresRawResult) {
                return $result;
            }

            if ($this->shouldPreserveForConditions($shortName)) {
                if ($result === null || is_bool($result)) {
                    return $result;
                }
            }

            return $this->resultFormatter->format($result);
        } catch (CompilationException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new CompilationException(
                "Error processing function $functionName: " . $e->getMessage()
            );
        }
    }

    private function shouldPreserveForConditions(string $functionName): bool
    {
        return in_array($functionName, ['index', 'str-index', 'is-bracketed', 'unquote'], true);
    }

    private function handleUnknownFunction(string $functionName, array $args): string
    {
        $argsList = implode(', ', array_map(
            $this->resultFormatter->format(...),
            $args
        ));

        return "$functionName($argsList)";
    }
}
