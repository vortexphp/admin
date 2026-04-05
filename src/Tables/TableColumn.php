<?php

declare(strict_types=1);

namespace Vortex\Admin\Tables;

use Vortex\Database\Model;

/**
 * One index column: subclass per {@see displayKind()} (Twig {@code admin/resource/cells/{kind}.twig}).
 */
abstract class TableColumn
{
    public function __construct(
        public readonly string $name,
        public readonly string $label,
        private readonly ?string $sortDatabaseColumn = null,
        private readonly bool $togglingEnabled = true,
        private readonly bool $startsCollapsed = false,
    ) {
    }

    abstract public function displayKind(): string;

    /**
     * When non-null, the index can sort by this SQL column (allowlisted identifier) via {@code sort} / {@code sort_dir} query params.
     */
    public function sortDatabaseColumn(): ?string
    {
        return $this->sortDatabaseColumn;
    }

    /**
     * When true, this column appears in the index “Columns” picker and can be shown or hidden (persisted in the browser).
     */
    public function togglingEnabled(): bool
    {
        return $this->togglingEnabled;
    }

    /**
     * When true (and {@see togglingEnabled()}), the column is hidden until enabled in the picker when there is no saved preference yet.
     */
    public function startsCollapsed(): bool
    {
        return $this->startsCollapsed;
    }

    /**
     * @return array<string, mixed>
     */
    public function toViewArray(): array
    {
        return [
            'kind' => $this->displayKind(),
            'name' => $this->name,
            'label' => $this->label,
            'sortable' => $this->sortDatabaseColumn !== null,
            'toggleable' => $this->togglingEnabled,
            'startsCollapsed' => $this->startsCollapsed,
        ];
    }

    /**
     * Normalize attribute value for the index row payload (Twig reads {@code row[col.name]}).
     */
    public function formatCellValue(mixed $value): mixed
    {
        return $value;
    }

    /**
     * Raw value before {@see formatCellValue()} (e.g. a relation column reads a nested model).
     */
    public function resolveRowValue(Model $row): mixed
    {
        return $row->{$this->name} ?? null;
    }

    /**
     * Relation paths passed to {@code QueryBuilder::with()} so cells can read eager-loaded data.
     * Use the same strings as in your model’s {@see \Vortex\Database\Model::eagerRelations()} keys (dot paths allowed).
     *
     * @return list<string>
     */
    public function eagerRelationPaths(): array
    {
        return [];
    }

    protected static function defaultLabel(string $name): string
    {
        $name = str_replace('_', ' ', $name);

        return ucwords($name);
    }
}
