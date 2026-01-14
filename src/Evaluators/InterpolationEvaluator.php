<?php

declare(strict_types=1);

namespace DartSass\Evaluators;

use Closure;
use DartSass\Parsers\ParserFactory;
use DartSass\Parsers\Syntax;
use DartSass\Utils\ValueFormatter;
use Exception;

use function is_string;
use function preg_match;
use function preg_replace_callback;
use function str_contains;
use function trim;

readonly class InterpolationEvaluator
{
    public function __construct(private ValueFormatter $valueFormatter, private ParserFactory $parserFactory) {}

    public function evaluate(string $string, Closure $evaluateExpression): string
    {
        if (! str_contains($string, '$') && ! str_contains($string, '#{')) {
            return $string;
        }

        do {
            $old = $string;

            // Process #{...} interpolations
            $string = $this->processHashInterpolations($string, $evaluateExpression);

            $string = $this->processInlineVariables($string, $evaluateExpression);

        } while ($string !== $old);

        return $string;
    }

    private function processHashInterpolations(string $string, Closure $evaluateExpression): string
    {
        return preg_replace_callback('/#\{([^}]+)}/', function ($matches) use ($evaluateExpression) {
            $expr = $matches[1];
            $expr = trim($expr, '"\' ');

            // Handle nested interpolations
            if (str_contains($expr, '#{')) {
                $expr = $this->evaluate($expr, $evaluateExpression);
            }

            // Evaluate expression
            try {
                $parser = $this->parserFactory->create($expr, Syntax::SCSS);
                $ast    = $parser->parseExpression();
                $value  = $evaluateExpression($ast);

                return $this->unwrapQuotedValue($value);
            } catch (Exception) {
                return $expr;
            }
        }, $string);
    }

    private function processInlineVariables(string $string, Closure $evaluateExpression): string
    {
        return preg_replace_callback('/\$[a-zA-Z_-][a-zA-Z0-9_-]*/', function ($matches) use ($evaluateExpression) {
            $varName = $matches[0];

            try {
                $value = $evaluateExpression($varName);
                $value = $this->unwrapQuotedValue($value);

                // Handle nested interpolations in the value
                if (is_string($value) && str_contains($value, '#{')) {
                    $value = $this->evaluate($value, $evaluateExpression);
                }

                return $this->valueFormatter->format($value);
            } catch (Exception) {
                return $varName;
            }
        }, $string);
    }

    private function unwrapQuotedValue(mixed $value): mixed
    {
        if (is_string($value) && preg_match('/^(["\']).*\1$/', $value)) {
            return trim($value, '"\'');
        }

        return $value;
    }
}
