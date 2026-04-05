<?php

declare(strict_types=1);

namespace Vortex\Admin\Tables\Columns;

use Vortex\Admin\Support\PublicAssetUrl;
use Vortex\Admin\Tables\TableColumn;

/**
 * Renders a stored URL or site path as a thumbnail. Relative values become root-absolute paths;
 * with {@code app.url} configured they become full {@code https://…} URLs for {@code <img src>}.
 * Unsafe {@code javascript:} / {@code data:} URLs are blanked for display.
 */
final class ImageColumn extends TableColumn
{
    public function __construct(
        string $name,
        string $label,
        private readonly int $maxHeightPx = 48,
        private readonly ?int $maxWidthPx = null,
        private readonly bool $openOriginalInNewTab = false,
        private readonly string $emptyLabel = '—',
        ?string $sortDatabaseColumn = null,
        bool $togglingEnabled = true,
        bool $startsCollapsed = false,
    ) {
        parent::__construct($name, $label, $sortDatabaseColumn, $togglingEnabled, $startsCollapsed);
    }

    public static function make(string $name, ?string $label = null): self
    {
        return new self($name, $label ?? self::defaultLabel($name));
    }

    public function label(string $label): self
    {
        return new self($this->name, $label, $this->maxHeightPx, $this->maxWidthPx, $this->openOriginalInNewTab, $this->emptyLabel, $this->sortDatabaseColumn(), $this->togglingEnabled(), $this->startsCollapsed());
    }

    public function size(int $maxHeightPx, ?int $maxWidthPx = null): self
    {
        return new self($this->name, $this->label, $maxHeightPx, $maxWidthPx, $this->openOriginalInNewTab, $this->emptyLabel, $this->sortDatabaseColumn(), $this->togglingEnabled(), $this->startsCollapsed());
    }

    public function openOriginalInNewTab(): self
    {
        return new self($this->name, $this->label, $this->maxHeightPx, $this->maxWidthPx, true, $this->emptyLabel, $this->sortDatabaseColumn(), $this->togglingEnabled(), $this->startsCollapsed());
    }

    public function sortable(?string $databaseColumn = null): self
    {
        $col = $databaseColumn ?? $this->name;

        return new self($this->name, $this->label, $this->maxHeightPx, $this->maxWidthPx, $this->openOriginalInNewTab, $this->emptyLabel, $col, $this->togglingEnabled(), $this->startsCollapsed());
    }

    public function alwaysVisible(): self
    {
        return new self($this->name, $this->label, $this->maxHeightPx, $this->maxWidthPx, $this->openOriginalInNewTab, $this->emptyLabel, $this->sortDatabaseColumn(), false, false);
    }

    public function collapsedByDefault(): self
    {
        return new self($this->name, $this->label, $this->maxHeightPx, $this->maxWidthPx, $this->openOriginalInNewTab, $this->emptyLabel, $this->sortDatabaseColumn(), true, true);
    }

    public function displayKind(): string
    {
        return 'image';
    }

    public function formatCellValue(mixed $value): mixed
    {
        return PublicAssetUrl::forImgSrc($value);
    }

    public function toViewArray(): array
    {
        return parent::toViewArray() + [
            'maxHeightPx' => $this->maxHeightPx,
            'maxWidthPx' => $this->maxWidthPx,
            'openOriginalInNewTab' => $this->openOriginalInNewTab,
            'emptyLabel' => $this->emptyLabel,
        ];
    }
}
