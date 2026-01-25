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
        int $line = 0
    ) {
        parent::__construct(NodeType::COLOR, $line);
    }

    public function __toString(): string
    {
        return ColorFormat::formatFromFunction($this->functionName, $this->components, $this->alpha);
    }
}
