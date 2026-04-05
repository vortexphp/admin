<?php

declare(strict_types=1);

namespace Vortex\Admin\Forms;

use Vortex\Database\Model;

/**
 * Foreign-key select: options are loaded from a related {@see Model} (label + id columns configurable).
 * Field {@see name} must be the FK attribute (e.g. {@code category_id}).
 *
 * @phpstan-type OptionsLoader callable(): iterable<Model>
 */
final class BelongsToSelectField extends FormField
{
    /**
     * @param class-string<Model> $relatedModel
     * @param (callable(): iterable<Model>)|null $loadRelated
     */
    public function __construct(
        string $name,
        string $label,
        public readonly string $relatedModel,
        private readonly string $optionLabelAttribute = 'name',
        private readonly string $optionValueAttribute = 'id',
        private readonly ?string $orderByColumn = 'id',
        private readonly string $orderDirection = 'ASC',
        private readonly bool $emptyOption = true,
        private readonly string $emptyLabel = '— Select —',
        private readonly bool $nullable = false,

        private $loadRelated = null,
    ) {
        parent::__construct($name, $label);
    }

    /**
     * @param class-string<Model> $relatedModel
     */
    public static function make(
        string $name,
        string $relatedModel,
        ?string $label = null,
        string $optionLabelAttribute = 'name',
        string $optionValueAttribute = 'id',
    ): self {
        $l = $label ?? self::defaultLabel($name);

        return new self($name, $l, $relatedModel, $optionLabelAttribute, $optionValueAttribute);
    }

    public function label(string $label): static
    {
        return new self(
            $this->name,
            $label,
            $this->relatedModel,
            $this->optionLabelAttribute,
            $this->optionValueAttribute,
            $this->orderByColumn,
            $this->orderDirection,
            $this->emptyOption,
            $this->emptyLabel,
            $this->nullable,
            $this->loadRelated,
        );
    }

    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        return new self(
            $this->name,
            $this->label,
            $this->relatedModel,
            $this->optionLabelAttribute,
            $this->optionValueAttribute,
            $column,
            $direction,
            $this->emptyOption,
            $this->emptyLabel,
            $this->nullable,
            $this->loadRelated,
        );
    }

    /**
     * Do not add a SQL {@code ORDER BY} (e.g. when using a custom {@see usingRelatedLoader()} that already defines order).
     */
    public function withoutOrder(): static
    {
        return new self(
            $this->name,
            $this->label,
            $this->relatedModel,
            $this->optionLabelAttribute,
            $this->optionValueAttribute,
            null,
            $this->orderDirection,
            $this->emptyOption,
            $this->emptyLabel,
            $this->nullable,
            $this->loadRelated,
        );
    }

    public function withoutEmptyOption(): static
    {
        return new self(
            $this->name,
            $this->label,
            $this->relatedModel,
            $this->optionLabelAttribute,
            $this->optionValueAttribute,
            $this->orderByColumn,
            $this->orderDirection,
            false,
            $this->emptyLabel,
            $this->nullable,
            $this->loadRelated,
        );
    }

    /**
     * Empty submission is stored as {@code null} (nullable FK).
     */
    public function nullable(): static
    {
        return new self(
            $this->name,
            $this->label,
            $this->relatedModel,
            $this->optionLabelAttribute,
            $this->optionValueAttribute,
            $this->orderByColumn,
            $this->orderDirection,
            $this->emptyOption,
            $this->emptyLabel,
            true,
            $this->loadRelated,
        );
    }

    /**
     * Override how related rows are fetched (tests or custom scopes).
     *
     * @param callable(): iterable<Model> $loader
     */
    public function usingRelatedLoader(callable $loader): static
    {
        return new self(
            $this->name,
            $this->label,
            $this->relatedModel,
            $this->optionLabelAttribute,
            $this->optionValueAttribute,
            $this->orderByColumn,
            $this->orderDirection,
            $this->emptyOption,
            $this->emptyLabel,
            $this->nullable,
            $loader,
        );
    }

    public function inputKind(): string
    {
        return 'select';
    }

    public function normalizeRequestValue(mixed $raw): mixed
    {
        if ($raw === null) {
            return $this->nullable ? null : '';
        }
        $s = is_string($raw) ? trim($raw) : (is_scalar($raw) ? (string) $raw : '');
        if ($s === '' && $this->nullable) {
            return null;
        }

        return $s;
    }

    /**
     * @return array<string, string>
     */
    public function optionMap(): array
    {
        $options = [];
        foreach ($this->iterRelatedModels() as $row) {
            $vid = $row->{$this->optionValueAttribute} ?? null;
            if ($vid === null) {
                continue;
            }
            $options[(string) $vid] = (string) ($row->{$this->optionLabelAttribute} ?? '');
        }

        return $options;
    }

    public function toViewArray(): array
    {
        return parent::toViewArray() + [
            'options' => $this->optionMap(),
            'emptyOption' => $this->emptyOption,
            'emptyLabel' => $this->emptyLabel,
        ];
    }

    /**
     * @return iterable<Model>
     */
    private function iterRelatedModels(): iterable
    {
        if ($this->loadRelated !== null) {
            return ($this->loadRelated)();
        }
        $q = $this->relatedModel::query();
        if ($this->orderByColumn !== null && $this->orderByColumn !== '') {
            $q->orderBy($this->orderByColumn, $this->orderDirection);
        }

        return $q->get();
    }
}
