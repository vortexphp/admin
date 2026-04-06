<?php

declare(strict_types=1);

namespace Vortex\Admin\Http;

use Vortex\Admin\AdminPage;
use Vortex\Http\Response;

final class AdminPageController extends AdminHttpController
{
    /**
     * @param class-string<AdminPage> $pageClass
     */
    public function render(string $pageClass): Response
    {
        return $this->adminView($pageClass::view(), [
            'title' => $pageClass::title(),
            'description' => $pageClass::description(),
            'pageDescription' => $pageClass::description(),
            'adminPage' => $pageClass::slug(),
        ]);
    }
}
