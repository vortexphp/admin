<?php

declare(strict_types=1);

namespace Vortex\Admin\Tables\Columns;

use Vortex\Admin\Tables\TableColumn;

/**
 * Renders a hex color as a swatch + code; only validates {@code #rgb}, {@code #rrggbb}, {@code #rrggbbaa}.
 */
final class ColorColumn extends TableColumn
{
    public function __construct(
        string $name,
        string $label,
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
        return new self($this->name, $label, $this->emptyLabel, $this->sortDatabaseColumn(), $this->togglingEnabled(), $this->startsCollapsed());
    }

    public function sortable(?string $databaseColumn = null): self
    {
        $col = $databaseColumn ?? $this->name;

        return new self($this->name, $this->label, $this->emptyLabel, $col, $this->togglingEnabled(), $this->startsCollapsed());
    }

    public function alwaysVisible(): self
    {
        return new self($this->name, $this->label, $this->emptyLabel, $this->sortDatabaseColumn(), false, false);
    }

    public function collapsedByDefault(): self
    {
        return new self($this->name, $this->label, $this->emptyLabel, $this->sortDatabaseColumn(), true, true);
    }

    public function displayKind(): string
    {
        return 'color';
    }

    public function formatCellValue(mixed $value): mixed
    {
        $hex = self::normalizeHex($value);

        return $hex ?? '';
    }

    public function toViewArray(): array
    {
        return parent::toViewArray() + ['emptyLabel' => $this->emptyLabel];
    }

    /**
     * @return non-empty-string|null Lowercase {@code #rrggbb} form (or 3/8-digit variants as normalized by browser).
     */
    public static function normalizeHex(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $s = trim(is_scalar($value) || $value instanceof \Stringable ? (string) $value : '');
        if ($s === '') {
            return null;
        }
        if ($s[0] !== '#') {
            if (preg_match('/^[0-9a-f]{3}$/i', $s) === 1) {
                $s = '#' . strtolower($s);
            } elseif (preg_match('/^[0-9a-f]{6}$/i', $s) === 1) {
                $s = '#' . strtolower($s);
            } elseif (preg_match('/^[0-9a-f]{8}$/i', $s) === 1) {
                $s = '#' . strtolower($s);
            } else {
                return null;
            }
        } else {
            $body = strtolower(substr($s, 1));
            if (preg_match('/^[0-9a-f]{3}$/', $body) !== 1
                && preg_match('/^[0-9a-f]{6}$/', $body) !== 1
                && preg_match('/^[0-9a-f]{8}$/', $body) !== 1) {
                return null;
            }
            $s = '#' . $body;
        }

        return $s;
    }
}
