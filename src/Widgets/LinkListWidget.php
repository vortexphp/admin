<?php

declare(strict_types=1);

namespace Vortex\Admin\Widgets;

/**
 * Generic linked rows (label, href, optional secondary line).
 *
 * @param list<array{label: string, href: string, description?: string|null}> $items
 */
final class LinkListWidget implements Widget
{
    /**
     * @param list<array{label: string, href: string, description?: string|null}> $items
     */
    public function __construct(
        private readonly ?string $title,
        private readonly array $items,
    ) {
    }

    public function toViewArray(): array
    {
        return [
            'kind' => 'link_list',
            'title' => $this->title,
            'items' => $this->items,
        ];
    }
}
