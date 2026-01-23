<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

use DartSass\Modules\ColorFormat;
use Stringable;

final class ColorNode extends AstNode implements Stringable
{
    public function __construct(
        public string $functionName,
        public array $components,
        public ?float $alpha = null,
        public int $line = 0
    ) {
        $properties = [
            'function_name' => $functionName,
            'components'    => $components,
            'alpha'         => $alpha,
            'line'          => $line,
        ];

        parent::__construct('color', $properties);
    }

    public function __toString(): string
    {
        return ColorFormat::formatFromFunction($this->functionName, $this->components, $this->alpha);
    }
}
