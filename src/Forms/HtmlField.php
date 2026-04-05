<?php

declare(strict_types=1);

namespace Vortex\Admin\Forms;

/**
 * Rich HTML edited with Quill (CDN). Stored value is HTML; sanitize on display in your app when needed.
 */
final class HtmlField extends FormField
{
    public function __construct(
        string $name,
        string $label,
        private readonly int $minHeightPx = 220,
    ) {
        parent::__construct($name, $label);
    }

    public static function make(string $name, ?string $label = null): self
    {
        return new self($name, $label ?? self::defaultLabel($name));
    }

    public function minHeight(int $px): self
    {
        return new self($this->name, $this->label, $px);
    }

    public function label(string $label): static
    {
        return new self($this->name, $label, $this->minHeightPx);
    }

    public function inputKind(): string
    {
        return 'html';
    }

    public function toViewArray(): array
    {
        return parent::toViewArray() + ['minHeightPx' => $this->minHeightPx];
    }
}
