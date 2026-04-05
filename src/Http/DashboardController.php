<?php

declare(strict_types=1);

namespace Vortex\Admin\Http;

use Vortex\Admin\DashboardWidgets;
use Vortex\Admin\Navigation;
use Vortex\Http\Response;

final class DashboardController extends AdminHttpController
{
    public function __construct(
        Navigation $navigation,
        private readonly DashboardWidgets $dashboardWidgets,
    ) {
        parent::__construct($navigation);
    }

    public function index(): Response
    {
        return $this->adminView('admin.dashboard', [
            'title' => 'Admin',
            'dashboardWidgets' => $this->dashboardWidgets->toViewArray(),
        ]);
    }
}
