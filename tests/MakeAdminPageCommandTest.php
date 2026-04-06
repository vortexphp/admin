<?php

declare(strict_types=1);

namespace Vortex\Admin\Tests;

use PHPUnit\Framework\TestCase;
use Vortex\Admin\Console\MakeAdminPageCommand;

final class MakeAdminPageCommandTest extends TestCase
{
    public function testDerivedCliName(): void
    {
        self::assertSame('make:admin-page', (new MakeAdminPageCommand())->name());
    }
}
