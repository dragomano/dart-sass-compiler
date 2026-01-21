<?php

declare(strict_types=1);

namespace DartSass\Evaluators;

use Closure;
use DartSass\Parsers\ParserFactory;
use DartSass\Parsers\Syntax;
use DartSass\Utils\ResultFormatterInterface;
use Exception;

use function is_array;
use function is_string;
use function preg_match;
use function preg_replace_callback;
use function str_contains;
use function trim;

readonly class InterpolationEvaluator
{
    public function __construct(
        private ResultFormatterInterface $resultFormatter,
        private ParserFactory $parserFactory
    ) {}

    public function evaluate(string $string, Closure $expression): string
    {
        if (! str_contains($string, '$') && ! str_contains($string, '#{')) {
            return $string;
        }

        do {
            $old = $string;

            // Process #{...} interpolations
            $string = $this->processHashInterpolations($string, $expression);

            $string = $this->processInlineVariables($string, $expression);

        } while ($string !== $old);

        return $string;
    }

    private function processHashInterpolations(string $string, Closure $expression): string
    {
        return preg_replace_callback('/#\{([^}]+)}/', function ($matches) use ($expression) {
            $expr = $matches[1];
            $expr = trim($expr, '"\' ');

            // Handle nested interpolations
            if (str_contains($expr, '#{')) {
                $expr = $this->evaluate($expr, $expression);
            }

            // Evaluate expression
            try {
                $parser = $this->parserFactory->create($expr, Syntax::SCSS);
                $ast    = $parser->parseExpression();
                $value  = $expression($ast);

                return $this->unwrapQuotedValue($value);
            } catch (Exception) {
                return $expr;
            }
        }, $string);
    }

    private function processInlineVariables(string $string, Closure $expression): string
    {
        return preg_replace_callback('/\$[a-zA-Z_-][a-zA-Z0-9_-]*/', function ($matches) use ($expression) {
            $varName = $matches[0];

            try {
                $value = $expression($varName);
                $value = $this->unwrapQuotedValue($value);

                // Handle nested interpolations in the value
                if (str_contains($value, '#{')) {
                    $value = $this->evaluate($value, $expression);
                }

                return $this->resultFormatter->format($value);
            } catch (Exception) {
                return $varName;
            }
        }, $string);
    }

    private function unwrapQuotedValue(mixed $value): string
    {
        if (is_array($value) && isset($value['value'])) {
            return isset($value['unit']) ? $value['value'] . $value['unit'] : (string) $value['value'];
        }

        if (is_string($value) && preg_match('/^(["\']).*\1$/', $value)) {
            return trim($value, '"\'');
        }

        return (string) $value;
    }
}
