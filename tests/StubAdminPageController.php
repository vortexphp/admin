<?php

declare(strict_types=1);

namespace Vortex\Admin\Tests;

use Vortex\Http\Controller;
use Vortex\Http\Response;

final class StubAdminPageController extends Controller
{
    public function index(): Response
    {
        return Response::make('ok');
    }
}
