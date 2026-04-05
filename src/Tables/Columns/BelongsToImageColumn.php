<?php

declare(strict_types=1);

namespace Vortex\Admin\Tables\Columns;

use Vortex\Admin\Support\PublicAssetUrl;
use Vortex\Admin\Tables\TableColumn;
use Vortex\Database\Model;

/**
 * Thumbnail from a scalar attribute on an eager-loaded {@see Model} relation (e.g. {@code author.avatar}).
 */
final class BelongsToImageColumn extends TableColumn
{
    public function __construct(
        private readonly string $relationPath,
        string $name,
        string $label,
        private readonly string $displayAttribute = 'avatar',
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

    /**
     * @param string $relationPath Dot path segments match eager-load keys (e.g. {@code author}).
     */
    public static function make(
        string $relationPath,
        ?string $label = null,
        string $displayAttribute = 'avatar',
    ): self {
        $name = str_replace('.', '_', $relationPath) . '_' . $displayAttribute;

        return new self(
            $relationPath,
            $name,
            $label ?? self::defaultLabel($name),
            $displayAttribute,
        );
    }

    public function label(string $label): self
    {
        return new self(
            $this->relationPath,
            $this->name,
            $label,
            $this->displayAttribute,
            $this->maxHeightPx,
            $this->maxWidthPx,
            $this->openOriginalInNewTab,
            $this->emptyLabel,
            $this->sortDatabaseColumn(),
            $this->togglingEnabled(),
            $this->startsCollapsed(),
        );
    }

    public function size(int $maxHeightPx, ?int $maxWidthPx = null): self
    {
        return new self(
            $this->relationPath,
            $this->name,
            $this->label,
            $this->displayAttribute,
            $maxHeightPx,
            $maxWidthPx,
            $this->openOriginalInNewTab,
            $this->emptyLabel,
            $this->sortDatabaseColumn(),
            $this->togglingEnabled(),
            $this->startsCollapsed(),
        );
    }

    public function openOriginalInNewTab(): self
    {
        return new self(
            $this->relationPath,
            $this->name,
            $this->label,
            $this->displayAttribute,
            $this->maxHeightPx,
            $this->maxWidthPx,
            true,
            $this->emptyLabel,
            $this->sortDatabaseColumn(),
            $this->togglingEnabled(),
            $this->startsCollapsed(),
        );
    }

    public function sortable(?string $databaseColumn = null): self
    {
        $col = $databaseColumn ?? BelongsToColumn::defaultForeignKeyForPath($this->relationPath);

        return new self(
            $this->relationPath,
            $this->name,
            $this->label,
            $this->displayAttribute,
            $this->maxHeightPx,
            $this->maxWidthPx,
            $this->openOriginalInNewTab,
            $this->emptyLabel,
            $col,
            $this->togglingEnabled(),
            $this->startsCollapsed(),
        );
    }

    public function alwaysVisible(): self
    {
        return new self(
            $this->relationPath,
            $this->name,
            $this->label,
            $this->displayAttribute,
            $this->maxHeightPx,
            $this->maxWidthPx,
            $this->openOriginalInNewTab,
            $this->emptyLabel,
            $this->sortDatabaseColumn(),
            false,
            false,
        );
    }

    public function collapsedByDefault(): self
    {
        return new self(
            $this->relationPath,
            $this->name,
            $this->label,
            $this->displayAttribute,
            $this->maxHeightPx,
            $this->maxWidthPx,
            $this->openOriginalInNewTab,
            $this->emptyLabel,
            $this->sortDatabaseColumn(),
            true,
            true,
        );
    }

    public function displayKind(): string
    {
        return 'image';
    }

    /**
     * @return list<string>
     */
    public function eagerRelationPaths(): array
    {
        return [$this->relationPath];
    }

    public function resolveRowValue(Model|array $row): mixed
    {
        if (is_array($row)) {
            return $row[$this->name] ?? null;
        }

        $related = $this->terminalRelatedModel($row);
        if ($related !== null) {
            return $related->{$this->displayAttribute} ?? null;
        }

        return null;
    }

    public function formatCellValue(mixed $value): mixed
    {
        return PublicAssetUrl::forImgSrc($value);
    }

    /**
     * @return array<string, mixed>
     */
    public function toViewArray(): array
    {
        return parent::toViewArray() + [
            'maxHeightPx' => $this->maxHeightPx,
            'maxWidthPx' => $this->maxWidthPx,
            'openOriginalInNewTab' => $this->openOriginalInNewTab,
            'emptyLabel' => $this->emptyLabel,
        ];
    }

    private function terminalRelatedModel(Model $row): ?Model
    {
        $current = $row;
        foreach (explode('.', $this->relationPath) as $segment) {
            $next = $current->{$segment} ?? null;
            if (! $next instanceof Model) {
                return null;
            }
            $current = $next;
        }

        return $current === $row ? null : $current;
    }
}
