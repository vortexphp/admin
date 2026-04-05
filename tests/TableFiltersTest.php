<?php

declare(strict_types=1);

namespace Vortex\Admin\Tests;

use PHPUnit\Framework\TestCase;
use Vortex\Admin\Tables\SelectFilter;
use Vortex\Admin\Tables\Table;
use Vortex\Admin\Tables\TableColumn;
use Vortex\Admin\Tables\TextFilter;

final class TableFiltersTest extends TestCase
{
    public function testWithFiltersPreservesColumns(): void
    {
        $base = Table::make(
            TableColumn::make('id'),
            TableColumn::make('title'),
        );
        self::assertSame([], $base->filters());

        $with = $base->withFilters(TextFilter::make('title', 'Search'));
        self::assertSame(['id', 'title'], $with->columnNames());
        self::assertCount(1, $with->filters());
        self::assertSame('f_title', $with->filters()[0]->queryParam());
        self::assertSame('Search', $with->filters()[0]->label);
    }

    public function testSelectFilterOptions(): void
    {
        $f = SelectFilter::make('status', ['draft' => 'Draft', 'live' => 'Live'], 'Status');
        self::assertSame(['draft' => 'Draft', 'live' => 'Live'], $f->options());
        self::assertSame('select', $f->inputKind());
    }
}
