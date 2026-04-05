<?php

declare(strict_types=1);

namespace Vortex\Admin\Widgets;

use Vortex\Admin\ResourceRegistry;

/**
 * Lists registered CRUD resources (same data as the default dashboard resource table).
 */
final class ResourceLinksWidget implements Widget
{
    public function __construct(
        private readonly ?string $title = 'Resources',
    ) {
    }

    public function toViewArray(): array
    {
        $items = [];
        foreach (ResourceRegistry::slugToClass() as $slug => $class) {
            $items[] = [
                'slug' => $slug,
                'label' => $class::pluralLabel(),
            ];
        }

        return [
            'kind' => 'resource_links',
            'title' => $this->title,
            'items' => $items,
        ];
    }
}
