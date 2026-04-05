<?php

declare(strict_types=1);

namespace Vortex\Admin\Forms;

final class NumberField extends FormField
{
    public function __construct(
        string $name,
        string $label,
        private readonly bool $integer = false,
        private readonly ?float $min = null,
        private readonly ?float $max = null,
        private readonly ?float $step = null,
        private readonly bool $emptyAsNull = false,
    ) {
        parent::__construct($name, $label);
    }

    public static function make(string $name, ?string $label = null): self
    {
        return new self($name, $label ?? self::defaultLabel($name));
    }

    public function integer(): self
    {
        return new self($this->name, $this->label, true, $this->min, $this->max, $this->step, $this->emptyAsNull);
    }

    public function min(float $min): self
    {
        return new self($this->name, $this->label, $this->integer, $min, $this->max, $this->step, $this->emptyAsNull);
    }

    public function max(float $max): self
    {
        return new self($this->name, $this->label, $this->integer, $this->min, $max, $this->step, $this->emptyAsNull);
    }

    public function step(float $step): self
    {
        return new self($this->name, $this->label, $this->integer, $this->min, $this->max, $step, $this->emptyAsNull);
    }

    public function emptyAsNull(): self
    {
        return new self($this->name, $this->label, $this->integer, $this->min, $this->max, $this->step, true);
    }

    public function label(string $label): static
    {
        return new self($this->name, $label, $this->integer, $this->min, $this->max, $this->step, $this->emptyAsNull);
    }

    public function inputKind(): string
    {
        return 'number';
    }

    public function toViewArray(): array
    {
        return parent::toViewArray() + [
            'integer' => $this->integer,
            'min' => $this->min,
            'max' => $this->max,
            'step' => $this->step,
        ];
    }

    public function normalizeRequestValue(mixed $raw): mixed
    {
        if ($raw === null || $raw === '') {
            return $this->emptyAsNull ? null : '';
        }
        if (! is_numeric($raw)) {
            return $this->emptyAsNull ? null : '';
        }

        return $this->integer ? (int) $raw : (float) (string) $raw;
    }
}
