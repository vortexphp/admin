<?php

declare(strict_types=1);

namespace Vortex\Admin\Tests\Fixtures;

use Vortex\Database\Model;

/** @internal fixture for codegen tests */
final class CodegenSampleModel extends Model
{
    protected static array $fillable = [
        'title',
        'is_active',
        'body',
        'amount',
        'user_email',
        'starts_at',
        'meta',
        'secret',
    ];

    /** @var array<string, string> */
    protected static array $casts = [
        'is_active' => 'bool',
        'amount' => 'float',
        'starts_at' => 'datetime',
        'meta' => 'json',
    ];
}
