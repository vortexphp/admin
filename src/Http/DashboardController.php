<?php

declare(strict_types=1);

namespace Vortex\Admin\Http;

use Vortex\Http\Controller;
use Vortex\Http\Response;
use Vortex\View\View;

final class DashboardController extends Controller
{
    public function index(): Response
    {
        return View::html('admin.dashboard', [
            'title' => 'Admin',
        ]);
    }
}
