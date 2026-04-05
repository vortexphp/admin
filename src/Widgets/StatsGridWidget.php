<?php

declare(strict_types=1);

namespace Vortex\Admin\Widgets;

/**
 * One row of metric cards (grid on wide screens).
 *
 * @param list<array{label: string, value: string, hint?: string|null}> $items
 */
final class StatsGridWidget implements Widget
{
    /**
     * @param list<array{label: string, value: string, hint?: string|null}> $items
     */
    public function __construct(
        private readonly ?string $title,
        private readonly array $items,
    ) {
    }

    public function toViewArray(): array
    {
        return [
            'kind' => 'stats_grid',
            'title' => $this->title,
            'items' => $this->items,
        ];
    }
}
