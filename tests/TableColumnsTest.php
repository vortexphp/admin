<?php

declare(strict_types=1);

namespace Vortex\Admin\Tests;

use PHPUnit\Framework\TestCase;
use Vortex\Admin\Tables\Columns\BadgeColumn;
use Vortex\Admin\Tables\Columns\BelongsToColumn;
use Vortex\Admin\Tables\Columns\BooleanColumn;
use Vortex\Admin\Tables\Columns\ColorColumn;
use Vortex\Admin\Tables\Columns\DatetimeColumn;
use Vortex\Admin\Tables\Columns\NumericColumn;
use Vortex\Admin\Tables\Columns\TextColumn;
use Vortex\Admin\Tables\Columns\ToggleColumn;
use Vortex\Admin\Tables\Table;
use Vortex\Database\Model;

final class TableColumnsTest extends TestCase
{
    public function testNumericFormats(): void
    {
        $c = NumericColumn::make('price', 'Price', 2)->withThousandsSeparator(',');
        self::assertSame('1,234.50', $c->formatCellValue(1234.5));
        self::assertSame('', $c->formatCellValue(null));
    }

    public function testBooleanLabels(): void
    {
        $c = BooleanColumn::make('active')->labels('On', 'Off', 'N/A');
        self::assertSame('On', $c->formatCellValue(1));
        self::assertSame('Off', $c->formatCellValue(false));
        self::assertSame('N/A', $c->formatCellValue(null));
    }

    public function testDatetimeColumn(): void
    {
        $c = DatetimeColumn::make('at', null, 'Y-m-d');
        self::assertSame('2024-01-15', $c->formatCellValue('2024-01-15 10:00:00'));
    }

    public function testTextColumnTruncates(): void
    {
        $c = TextColumn::make('body', null, 5);
        self::assertSame('1234…', $c->formatCellValue('123456789'));
    }

    public function testTextColumnSortableMetadata(): void
    {
        $plain = TextColumn::make('title');
        self::assertFalse($plain->toViewArray()['sortable']);
        $s = TextColumn::make('title')->sortable();
        self::assertTrue($s->toViewArray()['sortable']);
        self::assertSame('custom_col', $s->sortable('custom_col')->sortDatabaseColumn());
    }

    public function testColumnVisibilityFlags(): void
    {
        $plain = TextColumn::make('x');
        self::assertTrue($plain->togglingEnabled());
        self::assertFalse($plain->startsCollapsed());
        $v = $plain->alwaysVisible();
        self::assertFalse($v->togglingEnabled());
        $c = TextColumn::make('y')->collapsedByDefault();
        self::assertTrue($c->startsCollapsed());
        self::assertTrue($c->toViewArray()['startsCollapsed']);
    }

    public function testToggleColumnNormalizesBooleans(): void
    {
        $c = ToggleColumn::make('active')->labels('Locked', 'Open', 'N/A');
        self::assertTrue($c->formatCellValue(1));
        self::assertFalse($c->formatCellValue('0'));
        self::assertNull($c->formatCellValue(null));
        self::assertNull($c->formatCellValue('maybe'));
        $v = $c->toViewArray();
        self::assertSame('toggle', $v['kind']);
        self::assertSame('Locked', $v['trueLabel']);
    }

    public function testColorColumnNormalizesHex(): void
    {
        $c = ColorColumn::make('accent');
        self::assertSame('#10b981', $c->formatCellValue('#10B981'));
        self::assertSame('#10b981', $c->formatCellValue('10B981'));
        self::assertSame('', $c->formatCellValue('linear-gradient(red,blue)'));
        self::assertSame('', $c->formatCellValue('#gg0000'));
        self::assertSame('', $c->formatCellValue(''));
        $v = $c->toViewArray();
        self::assertSame('color', $v['kind']);
        self::assertArrayHasKey('emptyLabel', $v);
    }

    public function testBadgeColumnKeepsRawValue(): void
    {
        $c = BadgeColumn::make('status', 'Status', [
            'a' => ['label' => 'Alpha', 'tone' => 'success'],
        ]);
        self::assertSame('a', $c->formatCellValue('a'));
        $v = $c->toViewArray();
        self::assertSame('Alpha', $v['badges']['a']['label']);
        self::assertSame('success', $v['badges']['a']['tone']);
    }

    public function testBelongsToColumnUsesRelationAttribute(): void
    {
        $post = new BelongsToDemoPost();
        $cat = new BelongsToDemoCategory();
        $cat->name = 'Announcements';
        $post->category = $cat;
        $col = BelongsToColumn::make('category', 'Category', 'name');
        $raw = $col->resolveRowValue($post);
        self::assertSame('Announcements', $col->formatCellValue($raw));
        self::assertSame(['category'], $col->eagerRelationPaths());
    }

    public function testBelongsToColumnFallsBackToForeignKeyWhenUnloaded(): void
    {
        $post = new BelongsToDemoPost();
        $post->category_id = 42;
        $col = BelongsToColumn::make('category');
        $raw = $col->resolveRowValue($post);
        self::assertSame('42', $col->formatCellValue($raw));
    }

    public function testTableCollectsEagerPathsFromColumns(): void
    {
        $t = Table::make(
            TextColumn::make('id'),
            BelongsToColumn::make('author'),
            BelongsToColumn::make('category'),
        );
        self::assertEqualsCanonicalizing(['author', 'category'], $t->eagerRelationPaths());
    }

    public function testBelongsToNestedPath(): void
    {
        $post = new BelongsToDemoPost();
        $author = new BelongsToDemoUser();
        $country = new BelongsToDemoCountry();
        $country->code = 'BG';
        $author->country = $country;
        $post->author = $author;
        $col = BelongsToColumn::make('author.country', 'Origin', 'code');
        self::assertSame('BG', $col->formatCellValue($col->resolveRowValue($post)));
        self::assertSame(['author.country'], $col->eagerRelationPaths());
    }
}

final class BelongsToDemoPost extends Model
{
    /** @var list<string> */
    protected static array $fillable = ['title', 'category_id'];
}

final class BelongsToDemoCategory extends Model
{
    /** @var list<string> */
    protected static array $fillable = ['name'];
}

final class BelongsToDemoUser extends Model
{
    /** @var list<string> */
    protected static array $fillable = ['name', 'country_id'];
}

final class BelongsToDemoCountry extends Model
{
    /** @var list<string> */
    protected static array $fillable = ['code'];
}
