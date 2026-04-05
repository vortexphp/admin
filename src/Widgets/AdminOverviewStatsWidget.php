<?php

declare(strict_types=1);

namespace Vortex\Admin\Widgets;

use Vortex\Admin\ResourceRegistry;

/**
 * Dashboard metric row: current count of registered {@see Resource} classes (computed per request).
 */
final class AdminOverviewStatsWidget implements Widget
{
    public function toViewArray(): array
    {
        $count = count(ResourceRegistry::slugToClass());

        return [
            'kind' => 'stats_grid',
            'title' => 'Overview',
            'items' => [
                [
                    'label' => 'Resources',
                    'value' => (string) $count,
                    'hint' => $count === 0 ? 'Configure discover or resources in config/admin.php' : 'Available from this dashboard',
                ],
            ],
        ];
    }
}
