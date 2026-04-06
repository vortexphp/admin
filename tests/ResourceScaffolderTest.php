<?php

declare(strict_types=1);

namespace Vortex\Admin\Tests;

use PHPUnit\Framework\TestCase;
use Vortex\Admin\Codegen\ResourceScaffolder;
use Vortex\Admin\Tests\Fixtures\CodegenSampleModel;

final class ResourceScaffolderTest extends TestCase
{
    public function testGenerateMapsFillableToColumnsAndFields(): void
    {
        $out = ResourceScaffolder::generate(CodegenSampleModel::class, 'SampleResource', 'samples');
        $src = $out['contents'];

        self::assertStringContainsString('namespace App\\Admin\\Resources;', $src);
        self::assertStringContainsString('final class SampleResource extends Resource', $src);
        self::assertStringContainsString("return 'samples';", $src);
        self::assertStringContainsString("TextColumn::make('id')->sortable()", $src);
        self::assertStringContainsString("BooleanColumn::make('is_active')->sortable()", $src);
        self::assertStringContainsString('ToggleField::make(', $src);
        self::assertStringContainsString("NumericColumn::make('amount', null, 2)->sortable()", $src);
        self::assertStringContainsString("DatetimeColumn::make('starts_at')->sortable()", $src);
        self::assertStringContainsString("EmailColumn::make('user_email')->sortable()", $src);
        self::assertStringContainsString("TextColumn::make('meta')", $src);
        self::assertStringContainsString("TextareaField::make('meta')", $src);
        self::assertStringContainsString("PasswordField::make('secret')", $src);
        self::assertStringNotContainsString("TextColumn::make('secret')", $src);
        self::assertStringNotContainsString("TextField::make('secret')", $src);
        self::assertStringContainsString("defaultTableSort", $src);
    }
}
