<?php

declare(strict_types=1);

namespace Vortex\Admin\Tables\Columns;

use Vortex\Admin\Tables\TableColumn;

/**
 * Maps raw cell values to display label + tone for a pill (Twig).
 *
 * @param array<string|int|float, array{label: string, tone?: string}> $badges key = stored value (matched as string)
 */
final class BadgeColumn extends TableColumn
{
    /**
     * @param array<string|int|float, array{label: string, tone?: string}> $badges
     */
    public function __construct(
        string $name,
        string $label,
        private readonly array $badges,
        private readonly string $fallbackLabel = '—',
        private readonly string $fallbackTone = 'neutral',
    ) {
        parent::__construct($name, $label);
    }

    /**
     * @param array<string|int|float, array{label: string, tone?: string}> $badges
     */
    public static function make(string $name, ?string $label, array $badges): self
    {
        return new self($name, $label ?? self::defaultLabel($name), $badges);
    }

    public function label(string $label): self
    {
        return new self($this->name, $label, $this->badges, $this->fallbackLabel, $this->fallbackTone);
    }

    public function displayKind(): string
    {
        return 'badge';
    }

    /**
     * Keep raw scalar in row; Twig resolves {@see toViewArray} {@code badges}.
     */
    public function formatCellValue(mixed $value): mixed
    {
        return $value;
    }

    public function toViewArray(): array
    {
        $norm = [];
        foreach ($this->badges as $k => $v) {
            $norm[(string) $k] = [
                'label' => $v['label'],
                'tone' => $v['tone'] ?? 'neutral',
            ];
        }

        return parent::toViewArray() + [
            'badges' => $norm,
            'fallbackLabel' => $this->fallbackLabel,
            'fallbackTone' => $this->fallbackTone,
        ];
    }
}
