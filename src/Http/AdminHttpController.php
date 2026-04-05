<?php

declare(strict_types=1);

namespace Vortex\Admin\Http;

use Vortex\Admin\Navigation;
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
        return View::html($name, array_merge($data, [
            'adminNavigation' => $this->navigation->toViewArray(),
        ]), $status);
    }
}
