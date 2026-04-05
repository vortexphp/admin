<?php

declare(strict_types=1);

namespace Vortex\Admin\Tests;

use PHPUnit\Framework\TestCase;
use Vortex\Admin\Http\ArrayIndexPaginator;
use Vortex\Admin\Tables\CustomTableRecords;
use Vortex\Admin\Tables\Table;
use Vortex\Admin\Tables\Columns\BooleanColumn;
use Vortex\Admin\Tables\Columns\TextColumn;

final class TableCustomRecordsTest extends TestCase
{
    public function testNormalizeListPreservesRows(): void
    {
        $rows = [['id' => 1, 'title' => 'A']];
        self::assertSame($rows, CustomTableRecords::normalize($rows));
    }

    public function testNormalizeMapFillsIdFromKeys(): void
    {
        $n = CustomTableRecords::normalize([
            1 => ['title' => 'First', 'slug' => 'first'],
            2 => ['title' => 'Second', 'id' => 99, 'slug' => 'second'],
        ]);
        self::assertSame(1, $n[0]['id']);
        self::assertSame(99, $n[1]['id']);
        self::assertSame('First', $n[0]['title']);
    }

    public function testSortByColumn(): void
    {
        $rows = [
            ['title' => 'b', 'n' => 2],
            ['title' => 'a', 'n' => 1],
        ];
        $asc = CustomTableRecords::sort($rows, 'title', 'asc');
        self::assertSame('a', $asc[0]['title']);
        $desc = CustomTableRecords::sort($rows, 'n', 'desc');
        self::assertSame(2, $desc[0]['n']);
    }

    public function testTableRecordsFluent(): void
    {
        $t = Table::make(TextColumn::make('title'))
            ->records(static fn (): array => [['id' => 1, 'title' => 'x']]);
        $p = $t->recordsProvider();
        self::assertNotNull($p);
        self::assertSame([['id' => 1, 'title' => 'x']], ($p)());
    }

    public function testWithFiltersPreservesRecordsCallback(): void
    {
        $cb = static fn (): array => [];
        $t = Table::make(TextColumn::make('a'))
            ->records($cb)
            ->withFilters();
        self::assertSame($cb, $t->recordsProvider());
    }

    public function testArrayIndexPaginatorUrls(): void
    {
        $p = new ArrayIndexPaginator(25, 10, 2, 'https://ex.test/admin/widgets?page=1');
        self::assertTrue($p->hasPages);
        self::assertSame(3, $p->last_page);
        self::assertSame(2, $p->page);
        self::assertStringContainsString('page=3', $p->urlForPage(3));
    }

    public function testBooleanColumnReadsArrayRow(): void
    {
        $c = BooleanColumn::make('is_featured');
        self::assertTrue($c->resolveRowValue(['is_featured' => true]));
    }
}
