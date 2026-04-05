<?php

declare(strict_types=1);

namespace Vortex\Admin\Tables;

use Vortex\Database\QueryBuilder;

/**
 * Exact match on a column when the submitted value exists in {@see $options}.
 */
final class SelectFilter extends TableFilter
{
    /**
     * @param array<string, string> $options value => label shown in the dropdown
     */
    public function __construct(
        string $column,
        private readonly array $options,
        ?string $label = null,
    ) {
        parent::__construct($column, $label ?? self::defaultLabel($column));
    }

    /**
     * @param array<string, string> $options
     */
    public static function make(string $column, array $options, ?string $label = null): self
    {
        return new self($column, $options, $label);
    }

    public function label(string $label): self
    {
        return new self($this->name, $this->options, $label);
    }

    public function inputKind(): string
    {
        return 'select';
    }

    public function options(): array
    {
        return $this->options;
    }

    public function apply(QueryBuilder $query, mixed $raw): void
    {
        if ($raw === null || $raw === '') {
            return;
        }
        $key = is_string($raw) ? $raw : (string) $raw;
        if (! array_key_exists($key, $this->options)) {
            return;
        }

        $query->where($this->name, $key);
    }
}
