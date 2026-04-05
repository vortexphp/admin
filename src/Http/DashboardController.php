<?php

declare(strict_types=1);

namespace Vortex\Admin\Http;

use Vortex\Admin\ResourceRegistry;
use Vortex\Http\Response;

final class DashboardController extends AdminHttpController
{
    public function index(): Response
    {
        $items = [];
        foreach (ResourceRegistry::slugToClass() as $slug => $class) {
            $items[] = [
                'slug' => $slug,
                'label' => $class::pluralLabel(),
            ];
        }

        return $this->adminView('admin.dashboard', [
            'title' => 'Admin',
            'resources' => $items,
        ]);
    }
}
