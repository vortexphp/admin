<?php

declare(strict_types=1);

namespace Vortex\Admin\Showcase;

use Vortex\Admin\Tables\GlobalSearchFilter;
use Vortex\Admin\Tables\SelectFilter;
use Vortex\Admin\Tables\Table;
use Vortex\Admin\Tables\TextFilter;

/**
 * Static demo rows and in-memory filter matching for {@see Table::records()} showcase (SQL filters are skipped there).
 */
final class ShowcaseTableData
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function allRows(): array
    {
        $rows = [];
        for ($i = 1; $i <= 35; $i++) {
            $series = ['Alpha', 'Beta', 'Gamma'][$i % 3];
            $rows[] = [
                'id' => $i,
                'title' => "Demo item {$i}: {$series} collection",
                'slug' => "demo-item-{$i}",
                'status' => $i % 4 === 0 ? 'draft' : 'published',
                'tier' => ['free', 'pro', 'enterprise'][$i % 3],
                'price_cents' => 100 * ($i % 20),
                'featured' => $i % 5 === 0,
                'created_at' => sprintf(
                    '2024-%02d-%02d %02d:00:00',
                    (($i % 12) + 1),
                    (($i % 28) + 1),
                    ($i % 24),
                ),
                'contact_email' => "user{$i}@example.test",
                'website' => "https://example.test/p/{$i}",
                'accent' => ['#22c55e', '#eab308', '#6366f1'][$i % 3],
                'notifications' => $i % 2 === 0,
                'cover' => "https://picsum.photos/seed/adm{$i}/96/64",
            ];
        }

        return $rows;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @param array<string, string> $filterValues query values keyed by {@see \Vortex\Admin\Tables\TableFilter::queryParam()}
     * @return list<array<string, mixed>>
     */
    public static function applyInMemoryFilters(array $rows, Table $table, array $filterValues): array
    {
        foreach ($table->filters() as $filter) {
            $param = $filter->queryParam();
            $raw = $filterValues[$param] ?? '';
            if ($raw === '') {
                continue;
            }
            if ($filter instanceof GlobalSearchFilter) {
                $term = self::normalizeSearchTerm((string) $raw);
                if ($term === '') {
                    continue;
                }
                $cols = $filter->searchColumns();
                $rows = array_values(array_filter(
                    $rows,
                    static function (array $row) use ($cols, $term): bool {
                        foreach ($cols as $c) {
                            $v = $row[$c] ?? null;
                            if ($v !== null && $v !== '' && str_contains(strtolower((string) $v), $term)) {
                                return true;
                            }
                        }

                        return false;
                    },
                ));

                continue;
            }
            if ($filter instanceof TextFilter) {
                $term = self::normalizeSearchTerm((string) $raw);
                if ($term === '') {
                    continue;
                }
                $col = $filter->name;
                $rows = array_values(array_filter(
                    $rows,
                    static function (array $row) use ($col, $term): bool {
                        $v = $row[$col] ?? null;

                        return $v !== null && $v !== '' && str_contains(strtolower((string) $v), $term);
                    },
                ));

                continue;
            }
            if ($filter instanceof SelectFilter) {
                $key = (string) $raw;
                $opts = $filter->options();
                if (! array_key_exists($key, $opts)) {
                    continue;
                }
                $col = $filter->name;
                $rows = array_values(array_filter(
                    $rows,
                    static function (array $row) use ($col, $key): bool {
                        $v = $row[$col] ?? null;

                        return (string) $v === $key;
                    },
                ));
            }
        }

        return $rows;
    }

    private static function normalizeSearchTerm(string $raw): string
    {
        return strtolower(trim($raw));
    }
}
