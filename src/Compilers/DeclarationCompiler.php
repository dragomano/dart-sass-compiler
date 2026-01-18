<?php

declare(strict_types=1);

namespace DartSass\Compilers;

use Closure;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Utils\PositionTracker;
use DartSass\Utils\ValueFormatter;

use function current;
use function in_array;
use function key;
use function str_repeat;
use function strlen;

readonly class DeclarationCompiler
{
    public function __construct(private ValueFormatter $valueFormatter, private PositionTracker $positionTracker) {}

    public function compile(
        array $declarations,
        int $nestingLevel,
        string $parentSelector,
        array $options,
        array &$mappings,
        Closure $compileAst,
        Closure $evaluateExpression
    ): string {
        $css = '';

        foreach ($declarations as $declaration) {
            if ($declaration instanceof AstNode) {
                if ($declaration->type === 'comment') {
                    $indent = str_repeat('  ', $nestingLevel);
                    $commentCss = $indent . $declaration->properties['value'] . "\n";
                    $css .= $commentCss;

                    $this->positionTracker->updatePosition($commentCss);
                } else {
                    $css .= $compileAst([$declaration], $parentSelector, $nestingLevel);
                }
            } else {
                $indent   = str_repeat('  ', $nestingLevel);
                $property = key($declaration);
                $value    = current($declaration);

                $generatedPosition = $this->positionTracker->getCurrentPosition();

                $evaluatedValue = $evaluateExpression($value);
                if (in_array($evaluatedValue, [null, 'null', ''], true)) {
                    continue;
                }

                $formattedValue = $this->valueFormatter->format($evaluatedValue);
                $declarationCss = "$indent$property: " . $formattedValue . ";\n";

                $css .= $declarationCss;

                $this->positionTracker->updatePosition($declarationCss);

                if (($options['sourceMap'] ?? false)) {
                    $propertyGeneratedPosition = [
                        'line'   => $generatedPosition['line'],
                        'column' => $generatedPosition['column'] + strlen($indent),
                    ];

                    $originalLine   = $value->line ?? 0;
                    $originalColumn = $this->positionTracker->calculateIndentation($originalLine);

                    $mappings[] = [
                        'generated'   => $propertyGeneratedPosition,
                        'original'    => ['line' => $originalLine, 'column' => $originalColumn],
                        'sourceIndex' => 0,
                    ];
                }
            }
        }

        return $css;
    }
}
