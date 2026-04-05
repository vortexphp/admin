<?php

declare(strict_types=1);

namespace Vortex\Admin\Tests;

use PHPUnit\Framework\TestCase;
use Vortex\Admin\Tables\DeleteRowAction;
use Vortex\Admin\Tables\EditRowAction;
use Vortex\Admin\Tables\ModalRowAction;
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
        $t = Table::make(TextColumn::make('id'))->withActions(EditRowAction::make('Modify'));
        self::assertCount(1, $t->actions());
        $resolved = $t->actions()[0]->resolve('notes', ['id' => 5]);
        self::assertSame('link', $resolved['kind'] ?? null);
        self::assertSame('Modify', $resolved['label'] ?? null);
        self::assertSame('admin.resource.edit', $resolved['route'] ?? null);
    }

    public function testDeleteResolveRequiresId(): void
    {
        $a = DeleteRowAction::make();
        self::assertNull($a->resolve('x', []));
        $r = $a->resolve('x', ['id' => '12']);
        self::assertSame('post', $r['kind']);
        self::assertSame('admin.resource.destroy', $r['route']);
        self::assertSame(['slug' => 'x', 'id' => '12'], $r['routeParams']);
    }

    public function testModalRowActionFormFieldsResolve(): void
    {
        $a = ModalRowAction::form(
            'Flag',
            'Flag item',
            'admin.resource.index',
            static fn (string $slug, array $row): ?array => isset($row['id']) ? ['slug' => $slug, 'id' => (string) $row['id']] : null,
            static fn (string $slug, array $row): ?array => [
                ['type' => 'textarea', 'name' => 'reason', 'label' => 'Reason', 'required' => true],
            ],
        );
        $r = $a->resolve('posts', ['id' => 9]);
        self::assertIsArray($r);
        self::assertSame('modal', $r['kind'] ?? null);
        self::assertSame('Flag item', $r['title'] ?? null);
        self::assertSame('form', $r['content']['type'] ?? null);
        self::assertSame('admin.resource.index', $r['content']['route'] ?? null);
        self::assertSame(['slug' => 'posts', 'id' => '9'], $r['content']['routeParams'] ?? null);
        self::assertArrayHasKey('fields', $r['content']);
        self::assertCount(1, $r['content']['fields']);
    }

    public function testModalRowActionFormHtmlResolve(): void
    {
        $a = ModalRowAction::formHtml(
            'Note',
            'Add note',
            'admin.resource.index',
            static fn (string $slug, array $row): ?array => ['slug' => $slug, 'id' => '1'],
            static fn (string $slug, array $row): ?string => '<p class="text-sm text-zinc-400">Trusted HTML only.</p>',
        );
        $r = $a->resolve('x', ['id' => 1]);
        self::assertSame('modal', $r['kind'] ?? null);
        self::assertSame('form', $r['content']['type'] ?? null);
        self::assertArrayHasKey('body', $r['content']);
        self::assertStringContainsString('Trusted HTML', $r['content']['body']);
    }

    public function testModalRowActionStandaloneHtml(): void
    {
        $a = ModalRowAction::html(
            'View',
            'Details',
            static fn (string $slug, array $row): ?string => '<p>Row ' . ($row['id'] ?? '') . '</p>',
        );
        $r = $a->resolve('s', ['id' => '3']);
        self::assertSame('modal', $r['kind'] ?? null);
        self::assertSame('html', $r['content']['type'] ?? null);
        self::assertTrue($r['content']['showCloseFooter']);
        self::assertStringContainsString('Row 3', $r['content']['body']);
    }

    public function testModalRowActionInclude(): void
    {
        $a = ModalRowAction::include(
            'Custom',
            'Custom panel',
            'admin/partials/modal_shell.twig',
            static fn (string $slug, array $row): ?array => ['rowId' => (string) ($row['id'] ?? '')],
        );
        $r = $a->resolve('s', ['id' => 7]);
        self::assertSame('modal', $r['kind'] ?? null);
        self::assertSame('include', $r['content']['type'] ?? null);
        self::assertSame('admin/partials/modal_shell.twig', $r['content']['template'] ?? null);
        self::assertSame(['rowId' => '7'], $r['content']['with'] ?? null);
    }

    public function testModalIncludeRejectsUnsafePath(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ModalRowAction::include('x', 't', '../evil.twig', static fn (): array => []);
    }

    public function testModalFormReturnsNullWhenRouteParamsNull(): void
    {
        $a = ModalRowAction::form(
            'X',
            'T',
            'admin.dashboard',
            static fn (string $slug, array $row): ?array => null,
            static fn (): array => [['type' => 'text', 'name' => 'a', 'label' => 'A']],
        );
        self::assertNull($a->resolve('s', []));
    }
}
