<?php

declare(strict_types=1);

namespace Vortex\Admin\Tables;

use Vortex\Database\Model;

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
