<?php

declare(strict_types=1);

namespace Vortex\Admin\Forms;

final class CheckboxField extends FormField
{
    public function __construct(
        string $name,
        string $label,
        private readonly string $valueWhenChecked = '1',
    ) {
        parent::__construct($name, $label);
    }

    public static function make(string $name, ?string $label = null): self
    {
        return new self($name, $label ?? self::defaultLabel($name));
    }

    public function label(string $label): static
    {
        return new self($this->name, $label, $this->valueWhenChecked);
    }

    public function inputKind(): string
    {
        return 'checkbox';
    }

    public function toViewArray(): array
    {
        return parent::toViewArray() + ['valueWhenChecked' => $this->valueWhenChecked];
    }

    public function normalizeRequestValue(mixed $raw): mixed
    {
        if ($raw === null || $raw === '' || $raw === false || $raw === '0') {
            return false;
        }

        return $raw === true || $raw === 1 || $raw === '1' || $raw === 'on' || $raw === $this->valueWhenChecked;
    }
}
