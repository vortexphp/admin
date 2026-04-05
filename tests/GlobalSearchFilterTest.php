<?php

declare(strict_types=1);

namespace Vortex\Admin\Tests;

use PHPUnit\Framework\TestCase;
use Vortex\Admin\Tables\GlobalSearchFilter;

final class GlobalSearchFilterTest extends TestCase
{
    public function testRejectsInvalidColumnName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        GlobalSearchFilter::make('q', 'Search', ['title', 'bad;sql']);
    }

    public function testRejectsEmptyColumnList(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        GlobalSearchFilter::make('q', 'Search', []);
    }
}
