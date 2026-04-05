<?php

declare(strict_types=1);

namespace Vortex\Admin\Tests;

use PHPUnit\Framework\TestCase;
use Vortex\Admin\Forms\CheckboxField;
use Vortex\Admin\Forms\DateField;
use Vortex\Admin\Forms\HiddenField;
use Vortex\Admin\Forms\NumberField;
use Vortex\Admin\Forms\PasswordField;
use Vortex\Admin\Forms\SelectField;
use Vortex\Admin\Forms\TextField;

final class FormFieldsTest extends TestCase
{
    public function testCheckboxNormalizesOnAndOff(): void
    {
        $f = CheckboxField::make('ok');
        self::assertTrue($f->normalizeRequestValue('1'));
        self::assertTrue($f->normalizeRequestValue('on'));
        self::assertFalse($f->normalizeRequestValue(null));
    }

    public function testNumberInteger(): void
    {
        $f = NumberField::make('n')->integer();
        self::assertSame(7, $f->normalizeRequestValue('7'));
        self::assertSame('', $f->normalizeRequestValue(''));
    }

    public function testNumberEmptyAsNull(): void
    {
        $f = NumberField::make('n')->emptyAsNull();
        self::assertNull($f->normalizeRequestValue(''));
    }

    public function testDateNormalizes(): void
    {
        $f = DateField::make('d');
        self::assertSame('2024-06-01', $f->normalizeRequestValue('2024-06-01'));
    }

    public function testSelectToViewArrayContainsOptions(): void
    {
        $f = SelectField::make('role', ['admin' => 'Admin', 'user' => 'User']);
        $v = $f->toViewArray();
        self::assertSame('select', $v['inputKind']);
        self::assertSame(['admin' => 'Admin', 'user' => 'User'], $v['options']);
    }

    public function testHiddenAndPasswordKinds(): void
    {
        self::assertSame('hidden', HiddenField::make('x')->inputKind());
        self::assertSame('password', PasswordField::make('p')->inputKind());
        self::assertSame('text', TextField::make('t')->inputKind());
    }
}
