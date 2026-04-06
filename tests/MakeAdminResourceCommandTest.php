<?php

declare(strict_types=1);

namespace Vortex\Admin\Tests;

use PHPUnit\Framework\TestCase;
use Vortex\Admin\Console\MakeAdminResourceCommand;

final class MakeAdminResourceCommandTest extends TestCase
{
    public function testDerivedCliName(): void
    {
        self::assertSame('make:admin-resource', (new MakeAdminResourceCommand())->name());
    }
}
