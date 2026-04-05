<?php

declare(strict_types=1);

namespace Vortex\Admin\Forms;

/**
 * @param array<string, string> $options value => label (HTML option value / text)
 */
final class SelectField extends FormField
{
    /**
     * @param array<string, string> $options
     */
    public function __construct(
        string $name,
        string $label,
        public readonly array $options,
        private readonly bool $emptyOption = true,
        private readonly string $emptyLabel = '— Select —',
    ) {
        parent::__construct($name, $label);
    }

    /**
     * @param array<string, string> $options
     */
    public static function make(string $name, array $options, ?string $label = null): self
    {
        return new self($name, $label ?? self::defaultLabel($name), $options);
    }

    public function label(string $label): static
    {
        return new self($this->name, $label, $this->options, $this->emptyOption, $this->emptyLabel);
    }

    public function withoutEmptyOption(): self
    {
        return new self($this->name, $this->label, $this->options, false, $this->emptyLabel);
    }

    public function inputKind(): string
    {
        return 'select';
    }

    public function toViewArray(): array
    {
        return parent::toViewArray() + [
            'options' => $this->options,
            'emptyOption' => $this->emptyOption,
            'emptyLabel' => $this->emptyLabel,
        ];
    }
}
