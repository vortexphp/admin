<?php

declare(strict_types=1);

namespace Vortex\Admin\Forms;

/**
 * One field on a resource create/edit {@see Form}. Use concrete {@see TextField} or {@see TextareaField}.
 */
abstract class FormField
{
    public function __construct(
        public readonly string $name,
        public readonly string $label,
    ) {
    }

    /**
     * Drives the generic admin form template ({@code text} vs {@code textarea}).
     */
    abstract public function inputKind(): string;

    /**
     * @return static
     */
    public function label(string $label): static
    {
        return new static($this->name, $label);
    }

    protected static function defaultLabel(string $name): string
    {
        $name = str_replace('_', ' ', $name);

        return ucwords($name);
    }
}
