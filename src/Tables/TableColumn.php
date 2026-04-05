<?php

declare(strict_types=1);

namespace Vortex\Admin\Tables;

/**
 * One column on a resource index {@see Table} (model attribute + header label).
 */
final class TableColumn
{
    public function __construct(
        public readonly string $name,
        public readonly string $label,
    ) {
    }

    public static function make(string $name, ?string $label = null): self
    {
        return new self($name, $label ?? self::defaultLabel($name));
    }

    public function label(string $label): self
    {
        return new self($this->name, $label);
    }

    private static function defaultLabel(string $name): string
    {
        $name = str_replace('_', ' ', $name);

        return ucwords($name);
    }
}
