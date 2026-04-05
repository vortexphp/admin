<?php

declare(strict_types=1);

namespace Vortex\Admin\Tests;

use PHPUnit\Framework\TestCase;
use Vortex\Admin\Tables\DeleteAction;
use Vortex\Admin\Tables\EditAction;
use Vortex\Admin\Tables\Table;
use Vortex\Admin\Tables\Columns\TextColumn;

final class TableRowActionsTest extends TestCase
{
    public function testDefaultMakeIncludesEditAndDelete(): void
    {
        $t = Table::make(TextColumn::make('id'));
        self::assertCount(2, $t->actions());
    }

    public function testWithActionsReplacesDefaults(): void
    {
        $t = Table::make(TextColumn::make('id'))->withActions(EditAction::make('Modify'));
        self::assertCount(1, $t->actions());
        $resolved = $t->actions()[0]->resolve('notes', ['id' => 5]);
        self::assertSame('link', $resolved['kind'] ?? null);
        self::assertSame('Modify', $resolved['label'] ?? null);
        self::assertSame('admin.resource.edit', $resolved['route'] ?? null);
    }

    public function testDeleteResolveRequiresId(): void
    {
        $a = DeleteAction::make();
        self::assertNull($a->resolve('x', []));
        $r = $a->resolve('x', ['id' => '12']);
        self::assertSame('post', $r['kind']);
        self::assertSame('admin.resource.destroy', $r['route']);
        self::assertSame(['slug' => 'x', 'id' => '12'], $r['routeParams']);
    }
}
