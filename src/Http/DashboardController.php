<?php

declare(strict_types=1);

namespace Vortex\Admin\Http;

use Vortex\Admin\ResourceRegistry;
use Vortex\Http\Controller;
use Vortex\Http\Response;
use Vortex\View\View;

final class DashboardController extends Controller
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

        return View::html('admin.dashboard', [
            'title' => 'Admin',
            'resources' => $items,
        ]);
    }
}
