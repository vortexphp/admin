<?php

declare(strict_types=1);

namespace Vortex\Admin\Tables;

/**
 * One index column: subclass per {@see displayKind()} (Twig {@code admin/resource/cells/{kind}.twig}).
 */
abstract class TableColumn
{
    protected function __construct(
        public readonly string $name,
        public readonly string $label,
    ) {
    }

    abstract public function displayKind(): string;

    /**
     * @return array<string, mixed>
     */
    public function toViewArray(): array
    {
        return [
            'kind' => $this->displayKind(),
            'name' => $this->name,
            'label' => $this->label,
        ];
    }

    /**
     * Normalize attribute value for the index row payload (Twig reads {@code row[col.name]}).
     */
    public function formatCellValue(mixed $value): mixed
    {
        return $value;
    }

    protected static function defaultLabel(string $name): string
    {
        $name = str_replace('_', ' ', $name);

        return ucwords($name);
    }
}
