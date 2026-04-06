<?php

declare(strict_types=1);

namespace Vortex\Admin\Codegen;

use ReflectionClass;
use Vortex\Database\Model;

/**
 * Reads {@see Model} {@code $fillable} and {@code $casts} for admin resource codegen.
 */
final class ModelInspector
{
    /**
     * @param class-string<Model> $modelClass
     * @return array{
     *     fillable: list<string>,
     *     casts: array<string, string>,
     *     timestamps: bool,
     * }
     */
    public static function describe(string $modelClass): array
    {
        if (! is_subclass_of($modelClass, Model::class)) {
            throw new \InvalidArgumentException("Class must extend " . Model::class . ": {$modelClass}");
        }

        $refl = new ReflectionClass($modelClass);
        $defaults = $refl->getDefaultProperties();

        $fillable = $defaults['fillable'] ?? [];
        if (! is_array($fillable)) {
            $fillable = [];
        }
        /** @var list<string> $fillableList */
        $fillableList = [];
        foreach ($fillable as $k) {
            if (is_string($k) && $k !== '') {
                $fillableList[] = $k;
            }
        }

        $casts = $defaults['casts'] ?? [];
        if (! is_array($casts)) {
            $casts = [];
        }
        /** @var array<string, string> $castMap */
        $castMap = [];
        foreach ($casts as $attr => $type) {
            if (is_string($attr) && $attr !== '' && is_string($type) && $type !== '') {
                $castMap[$attr] = strtolower(trim($type));
            }
        }

        $timestamps = $defaults['timestamps'] ?? true;
        if (! is_bool($timestamps)) {
            $timestamps = true;
        }

        return [
            'fillable' => $fillableList,
            'casts' => $castMap,
            'timestamps' => $timestamps,
        ];
    }
}
