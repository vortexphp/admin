<?php

declare(strict_types=1);

namespace Vortex\Admin\Tests;

use PHPUnit\Framework\TestCase;
use Vortex\Admin\Showcase\ShowcaseTableData;
use Vortex\Admin\Tables\Columns\TextColumn;
use Vortex\Admin\Tables\GlobalSearchFilter;
use Vortex\Admin\Tables\SelectFilter;
use Vortex\Admin\Tables\Table;
use Vortex\Admin\Tables\TextFilter;

final class ShowcaseTableDataTest extends TestCase
{
    public function testApplyInMemoryFiltersNarrowsRows(): void
    {
        $table = Table::make(TextColumn::make('title'), TextColumn::make('slug'))
            ->withFilters(
                TextFilter::make('title'),
                SelectFilter::make('status', ['a' => 'A', 'b' => 'B']),
                GlobalSearchFilter::make('search', 'Search', ['title', 'slug']),
            )
            ->withActions()
            ->records(fn (): array => []);

        $rows = [
            ['id' => 1, 'title' => 'Hello world', 'slug' => 'hello', 'status' => 'a'],
            ['id' => 2, 'title' => 'Other', 'slug' => 'zzz', 'status' => 'b'],
        ];

        $out = ShowcaseTableData::applyInMemoryFilters($rows, $table, ['f_title' => 'Hello']);
        self::assertCount(1, $out);
        self::assertSame(1, $out[0]['id']);

        $out2 = ShowcaseTableData::applyInMemoryFilters($rows, $table, ['f_status' => 'b']);
        self::assertCount(1, $out2);
        self::assertSame(2, $out2[0]['id']);

        $out3 = ShowcaseTableData::applyInMemoryFilters($rows, $table, ['f_search' => 'zzz']);
        self::assertCount(1, $out3);
        self::assertSame(2, $out3[0]['id']);
    }
}
