<?php

declare(strict_types=1);

namespace Vortex\Admin\Tables;

use InvalidArgumentException;
use Vortex\Admin\SqlIdentifier;
use Vortex\Database\QueryBuilder;

/**
 * One search box that OR-matches {@code LIKE %term%} across several columns.
 */
final class GlobalSearchFilter extends TableFilter
{
    /**
     * @param list<string> $columns
     */
    public function __construct(
        string $name,
        string $label,
        private readonly array $columns,
    ) {
        parent::__construct($name, $label);
        if ($this->columns === []) {
            throw new InvalidArgumentException('GlobalSearchFilter requires at least one column');
        }
        foreach ($this->columns as $c) {
            if (! is_string($c) || ! SqlIdentifier::isSafe($c)) {
                throw new InvalidArgumentException("Invalid search column identifier: {$c}");
            }
        }
    }

    /**
     * @param list<string> $columns
     */
    public static function make(string $name, string $label, array $columns): self
    {
        return new self($name, $label, $columns);
    }

    public function label(string $label): self
    {
        return new self($this->name, $label, $this->columns);
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
        $like = '%' . $escaped . '%';

        $cols = $this->columns;
        $first = array_shift($cols);
        if ($first === null) {
            return;
        }

        $query->whereGroup(static function (QueryBuilder $q) use ($first, $cols, $like): void {
            $q->where($first, 'LIKE', $like);
            foreach ($cols as $col) {
                $q->orWhere($col, 'LIKE', $like);
            }
        });
    }
}
