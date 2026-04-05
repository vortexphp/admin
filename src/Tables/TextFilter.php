<?php

declare(strict_types=1);

namespace Vortex\Admin\Tables;

use Vortex\Database\QueryBuilder;

/**
 * Partial match on a column ({@code LIKE %value%}). Ignores empty input.
 */
final class TextFilter extends TableFilter
{
    public static function make(string $column, ?string $label = null): self
    {
        return new self($column, $label ?? self::defaultLabel($column));
    }

    public function label(string $label): self
    {
        return new self($this->name, $label);
    }

    public function inputKind(): string
    {
        return 'text';
    }

    public function apply(QueryBuilder $query, mixed $raw): void
    {
        if (! is_string($raw)) {
            return;
        }
        $trimmed = trim($raw);
        if ($trimmed === '') {
            return;
        }
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $trimmed);

        $query->where($this->name, 'LIKE', '%' . $escaped . '%');
    }
}
