<?php

declare(strict_types=1);

namespace Vortex\Admin\Http;

use Vortex\Admin\Navigation;
use Vortex\Admin\ResourceRegistry;
use Vortex\Http\Controller;
use Vortex\Http\Response;
use Vortex\View\View;

abstract class AdminHttpController extends Controller
{
    public function __construct(
        protected readonly Navigation $navigation,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function adminView(string $name, array $data = [], int $status = 200): Response
    {
        $sidebarResources = [];
        foreach (ResourceRegistry::slugToClass() as $s => $class) {
            $sidebarResources[] = ['slug' => $s, 'label' => $class::pluralLabel()];
        }

        return View::html($name, array_merge($data, [
            'adminNavigation' => $this->navigation->toViewArray(),
            'adminSidebarResources' => $sidebarResources,
        ]), $status);
    }
}
