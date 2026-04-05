<?php

declare(strict_types=1);

namespace Vortex\Admin\Tables;

/**
 * In-memory rows for {@see Table::records()}: normalize id-keyed maps and sort by column name.
 */
final class CustomTableRecords
{
    /**
     * @param mixed $raw Return value from {@see Table::records()} callback
     * @return list<array<string, mixed>>
     */
    public static function normalize(mixed $raw): array
    {
        if (! is_array($raw) || $raw === []) {
            return [];
        }
        if (array_is_list($raw)) {
            /** @var list<array<string, mixed>> $out */
            $out = [];
            foreach ($raw as $item) {
                if (is_array($item)) {
                    $out[] = $item;
                }
            }

            return $out;
        }
        $out = [];
        foreach ($raw as $id => $item) {
            if (! is_array($item)) {
                continue;
            }
            $row = $item;
            if (! array_key_exists('id', $row)) {
                $row['id'] = is_string($id) || is_int($id) ? $id : null;
            }
            $out[] = $row;
        }

        return $out;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    public static function sort(array $rows, ?string $column, string $dir): array
    {
        if ($column === null || $column === '' || $rows === []) {
            return $rows;
        }
        $mult = strtolower($dir) === 'desc' ? -1 : 1;
        usort($rows, static function (array $a, array $b) use ($column, $mult): int {
            $va = $a[$column] ?? null;
            $vb = $b[$column] ?? null;
            if (is_int($va) && is_int($vb)) {
                return ($va <=> $vb) * $mult;
            }
            if (is_float($va) && is_float($vb)) {
                return ($va <=> $vb) * $mult;
            }

            return ((string) $va <=> (string) $vb) * $mult;
        });

        return $rows;
    }
}
