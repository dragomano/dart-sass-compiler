<?php

declare(strict_types=1);

namespace DartSass\Compilers;

use Closure;
use DartSass\Parsers\Nodes\AstNode;
use DartSass\Utils\PositionTracker;
use DartSass\Utils\ResultFormatterInterface;
use DartSass\Utils\StringFormatter;

use function current;
use function key;
use function max;
use function str_repeat;
use function strlen;

readonly class DeclarationCompiler
{
    public function __construct(
        private ResultFormatterInterface $resultFormatter,
        private PositionTracker $positionTracker
    ) {}

    public function compile(
        array $declarations,
        int $nestingLevel,
        string $parentSelector,
        CompilerContext $context,
        Closure $compileAst,
        Closure $expression
    ): string {
        $css = '';

        foreach ($declarations as $declaration) {
            if ($declaration instanceof AstNode) {
                if ($declaration->type === 'comment') {
                    $indent = str_repeat('  ', $nestingLevel);

                    $commentCss = StringFormatter::concatMultiple([$indent, $declaration->properties['value'], "\n"]);

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

                $evaluatedValue = $expression($value);
                if ($evaluatedValue === null || $evaluatedValue === '') {
                    continue;
                }

                $formattedValue = $this->resultFormatter->format($evaluatedValue);
                $declarationCss = StringFormatter::concatMultiple([$indent, $property, ': ', $formattedValue, ";\n"]);

                $css .= $declarationCss;

                $this->positionTracker->updatePosition($declarationCss);

                if ($context->options['sourceMap'] ?? false) {
                    $generatedPosition = [
                        'line'   => $generatedPosition['line'] - 1,
                        'column' => $generatedPosition['column'] + strlen($indent),
                    ];

                    $originalPosition = [
                        'line'   => max(0, ($value->properties['property_line'] ?? 1) - 1),
                        'column' => max(0, ($value->properties['property_column'] ?? 1) - 1),
                    ];

                    $context->mappings[] = [
                        'generated'   => $generatedPosition,
                        'original'    => $originalPosition,
                        'sourceIndex' => 0,
                    ];
                }
            }
        }

        return $css;
    }
}
