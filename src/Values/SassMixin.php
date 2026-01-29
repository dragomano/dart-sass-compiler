<?php

declare(strict_types=1);

namespace DartSass\Values;

use DartSass\Handlers\MixinHandler;
use Stringable;

readonly class SassMixin implements Stringable
{
    public function __construct(
        private MixinHandler $mixinHandler,
        private string $mixinName
    ) {}

    public function __toString(): string
    {
        return $this->mixinName;
    }

    public function apply(array $args, ?array $content = null): string
    {
        return $this->mixinHandler->include($this->mixinName, $args, $content);
    }

    public function acceptsContent(): bool
    {
        $mixinDefinition = $this->getMixinDefinition();

        if ($mixinDefinition === null) {
            return false;
        }

        foreach ($mixinDefinition['body'] as $item) {
            if ($this->isContentDirective($item)) {
                return true;
            }
        }

        return false;
    }

    private function getMixinDefinition(): ?array
    {
        return $this->mixinHandler->getMixin($this->mixinName) ?? null;
    }

    private function isContentDirective(mixed $item): bool
    {
        if ($item === '@content') {
            return true;
        }

        if (is_object($item) && property_exists($item, 'name') && $item->name === '@content') {
            return true;
        }

        return false;
    }
}
