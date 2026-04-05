<?php

declare(strict_types=1);

namespace Vortex\Admin\Forms;

/**
 * Markdown body edited with EasyMDE (CDN) on the resource form.
 */
final class MarkdownField extends FormField
{
    public function __construct(
        string $name,
        string $label,
        private readonly int $minHeightPx = 240,
        private readonly string $placeholder = '',
    ) {
        parent::__construct($name, $label);
    }

    public static function make(string $name, ?string $label = null): self
    {
        return new self($name, $label ?? self::defaultLabel($name));
    }

    public function minHeight(int $px): self
    {
        return new self($this->name, $this->label, $px, $this->placeholder);
    }

    public function placeholder(string $p): self
    {
        return new self($this->name, $this->label, $this->minHeightPx, $p);
    }

    public function label(string $label): static
    {
        return new self($this->name, $label, $this->minHeightPx, $this->placeholder);
    }

    public function inputKind(): string
    {
        return 'markdown';
    }

    public function toViewArray(): array
    {
        return parent::toViewArray() + [
            'minHeightPx' => $this->minHeightPx,
            'placeholder' => $this->placeholder,
        ];
    }
}
