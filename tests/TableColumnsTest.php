<?php

declare(strict_types=1);

namespace Vortex\Admin\Tests;

use PHPUnit\Framework\TestCase;
use Vortex\Admin\Tables\Columns\BadgeColumn;
use Vortex\Admin\Tables\Columns\BooleanColumn;
use Vortex\Admin\Tables\Columns\ColorColumn;
use Vortex\Admin\Tables\Columns\DatetimeColumn;
use Vortex\Admin\Tables\Columns\NumericColumn;
use Vortex\Admin\Tables\Columns\TextColumn;

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
}
