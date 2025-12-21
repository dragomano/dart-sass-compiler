<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

final class SelectorNode extends AstNode
{
    public function __construct(
        public string $value,
        public int $line,
        public ?int $indent = null,
        public ?string $parent_selector = null
    ) {
        $properties = [
            'value' => $value,
            'line'  => $line,
        ];

        if ($indent !== null) {
            $properties['indent'] = $indent;
        }

        if ($parent_selector !== null) {
            $properties['parent_selector'] = $parent_selector;
        }

        parent::__construct('selector', $properties);
    }
}
