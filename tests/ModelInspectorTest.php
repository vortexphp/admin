<?php

declare(strict_types=1);

namespace Vortex\Admin\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Vortex\Admin\Codegen\ModelInspector;
use Vortex\Admin\Tests\Fixtures\CodegenSampleModel;

final class ModelInspectorTest extends TestCase
{
    public function testDescribeReadsFillableAndCasts(): void
    {
        $info = ModelInspector::describe(CodegenSampleModel::class);
        self::assertSame(
            ['title', 'is_active', 'body', 'amount', 'user_email', 'starts_at', 'meta', 'secret'],
            $info['fillable'],
        );
        self::assertSame(
            [
                'is_active' => 'bool',
                'amount' => 'float',
                'starts_at' => 'datetime',
                'meta' => 'json',
            ],
            $info['casts'],
        );
        self::assertTrue($info['timestamps']);
    }

    public function testDescribeRejectsNonModel(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ModelInspector::describe(\stdClass::class);
    }
}
