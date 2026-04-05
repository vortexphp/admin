<?php

declare(strict_types=1);

namespace Vortex\Admin\Tests;

use PHPUnit\Framework\TestCase;
use Vortex\Admin\DashboardWidgets;
use Vortex\Admin\Widgets\TextWidget;

final class DashboardWidgetsTest extends TestCase
{
    public function testClearAndAddOrder(): void
    {
        $d = DashboardWidgets::make();
        $d->add(new TextWidget('A', 'one'));
        $d->add(new TextWidget('B', 'two'));
        $d->clear();
        $d->add(new TextWidget(null, 'solo'));

        $rows = $d->toViewArray();
        self::assertCount(1, $rows);
        self::assertSame('text', $rows[0]['kind']);
        self::assertNull($rows[0]['title']);
        self::assertSame('solo', $rows[0]['body']);
    }
}
