<?php

declare(strict_types=1);

namespace Vortex\Admin\Http;

use JsonException;
use Vortex\Admin\Showcase\ShowcaseTableData;
use Vortex\Admin\SqlIdentifier;
use Vortex\Admin\Tables\Columns\BadgeColumn;
use Vortex\Admin\Tables\Columns\BooleanColumn;
use Vortex\Admin\Tables\Columns\ColorColumn;
use Vortex\Admin\Tables\Columns\DatetimeColumn;
use Vortex\Admin\Tables\Columns\EmailColumn;
use Vortex\Admin\Tables\Columns\ImageColumn;
use Vortex\Admin\Tables\Columns\NumericColumn;
use Vortex\Admin\Tables\Columns\TextColumn;
use Vortex\Admin\Tables\Columns\ToggleColumn;
use Vortex\Admin\Tables\Columns\UrlColumn;
use Vortex\Admin\Tables\CustomTableRecords;
use Vortex\Admin\Tables\LinkRowAction;
use Vortex\Admin\Tables\ModalRowAction;
use Vortex\Admin\Tables\SelectFilter;
use Vortex\Admin\Tables\Table;
use Vortex\Admin\Tables\TextFilter;
use Vortex\Http\Csrf;
use Vortex\Http\Request;
use Vortex\Http\Response;
use Vortex\Support\UrlHelp;

final class ShowcaseController extends AdminHttpController
{
    private const SLUG = 'showcase-tables';

    public function tables(): Response
    {
        $queryString = Request::hasCurrent() ? Request::query() : [];

        $filterValues = [];
        $tableTemplate = self::tableDefinition();
        foreach ($tableTemplate->filters() as $filter) {
            $param = $filter->queryParam();
            $raw = $queryString[$param] ?? '';
            if (is_array($raw)) {
                continue;
            }
            $filterValues[$param] = is_string($raw) ? $raw : (string) $raw;
        }

        $page = 1;
        if (isset($queryString['page'])) {
            $p = $queryString['page'];
            if (is_string($p) && ctype_digit($p)) {
                $page = max(1, (int) $p);
            }
        }

        $perPageResolution = self::resolveShowcasePerPage($queryString);
        $perPage = $perPageResolution['perPage'];
        $perPageOptions = $perPageResolution['options'];

        $sortState = self::resolveShowcaseSort($tableTemplate, $queryString);

        $listQuery = $filterValues;
        if (count($perPageOptions) > 1) {
            $listQuery['per_page'] = (string) $perPage;
        }
        if ($sortState['persist'] && $sortState['uiKey'] !== null) {
            $listQuery['sort'] = $sortState['uiKey'];
            $listQuery['sort_dir'] = $sortState['dir'];
        }

        $tableListUrl = route('admin.showcase.tables');
        $listPath = UrlHelp::withQuery($tableListUrl, $listQuery);

        $table = $tableTemplate->records(function () use ($filterValues, $tableTemplate): array {
            $rows = ShowcaseTableData::allRows();

            return ShowcaseTableData::applyInMemoryFilters($rows, $tableTemplate, $filterValues);
        });

        $provider = $table->recordsProvider();
        $raw = $provider !== null ? $provider() : [];
        $normalized = CustomTableRecords::normalize($raw);
        $sorted = CustomTableRecords::sort($normalized, $sortState['uiKey'], $sortState['dir']);
        $total = count($sorted);
        $offset = ($page - 1) * $perPage;
        $pageRows = array_slice($sorted, $offset, $perPage);

        $records = [];
        foreach ($pageRows as $row) {
            $records[] = self::buildRowPayload($table, $row);
        }
        $paginator = new ArrayIndexPaginator($total, $perPage, $page, $listPath);

        $tableRowActions = [];
        $slug = self::SLUG;
        foreach ($records as $record) {
            $cells = [];
            foreach ($table->actions() as $action) {
                $resolved = $action->resolve($slug, $record);
                if ($resolved !== null) {
                    $cells[] = $resolved;
                }
            }
            $tableRowActions[] = $cells;
        }

        $tableColumnsView = [];
        foreach ($table->columns() as $col) {
            $meta = $col->toViewArray();
            $sortDb = $col->sortDatabaseColumn();
            if ($sortDb !== null && SqlIdentifier::isSafe($sortDb)) {
                $isActive = $sortState['uiKey'] === $col->name;
                $nextDir = ($isActive && $sortState['dir'] === 'asc') ? 'desc' : 'asc';
                $meta['sortUrl'] = UrlHelp::withQuery($tableListUrl, array_merge($listQuery, [
                    'sort' => $col->name,
                    'sort_dir' => $nextDir,
                ]));
                $meta['sortActive'] = $isActive;
                $meta['sortDir'] = $isActive ? $sortState['dir'] : null;
            }
            $tableColumnsView[] = $meta;
        }

        $tableColumnPickerEnabled = $table->columnPickerUiEnabled();
        if ($tableColumnPickerEnabled) {
            $tableColumnPickerEnabled = false;
            foreach ($table->columns() as $c) {
                if ($c->togglingEnabled()) {
                    $tableColumnPickerEnabled = true;
                    break;
                }
            }
        }

        $tableColumnPickerMetaJson = '[]';
        if ($tableColumnPickerEnabled) {
            $pickerMeta = [];
            foreach ($table->columns() as $c) {
                $pickerMeta[] = [
                    'name' => $c->name,
                    'label' => $c->label,
                    'toggleable' => $c->togglingEnabled(),
                    'startsCollapsed' => $c->startsCollapsed(),
                ];
            }
            try {
                $tableColumnPickerMetaJson = json_encode($pickerMeta, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                $tableColumnPickerMetaJson = '[]';
            }
        }

        return $this->adminView('admin.showcase.tables', [
            'title' => 'Table showcase',
            'slug' => $slug,
            'tableListUrl' => $tableListUrl,
            'tableColumns' => $tableColumnsView,
            'tableFilters' => $table->filters(),
            'tableActions' => $table->actions(),
            'filterValues' => $filterValues,
            'perPageOptions' => $perPageOptions,
            'tablePerPageCurrent' => $perPage,
            'pagination' => $paginator,
            'records' => $records,
            'tableRowActions' => $tableRowActions,
            'tableSort' => [
                'key' => $sortState['uiKey'],
                'dir' => $sortState['dir'],
                'persist' => $sortState['persist'],
            ],
            'tableEmptyMessage' => $table->emptyMessage(),
            'tableColumnPickerEnabled' => $tableColumnPickerEnabled,
            'tableColumnPickerMetaJson' => $tableColumnPickerMetaJson,
            'csrfToken' => Csrf::token(),
        ]);
    }

    private static function tableDefinition(): Table
    {
        return Table::make(
            ImageColumn::make('cover', 'Cover')->size(40, 56)->collapsedByDefault(),
            TextColumn::make('title')->sortable()->alwaysVisible(),
            TextColumn::make('slug', 'Slug')->sortable()->collapsedByDefault(),
            BadgeColumn::make('status', 'Status', [
                'draft' => ['label' => 'Draft', 'tone' => 'warning'],
                'published' => ['label' => 'Published', 'tone' => 'success'],
            ])->sortable(),
            BadgeColumn::make('tier', 'Tier', [
                'free' => ['label' => 'Free', 'tone' => 'neutral'],
                'pro' => ['label' => 'Pro', 'tone' => 'info'],
                'enterprise' => ['label' => 'Enterprise', 'tone' => 'accent'],
            ])->sortable()->collapsedByDefault(),
            NumericColumn::make('price_cents', 'Price (¢)', 0)->sortable(),
            BooleanColumn::make('featured', 'Featured')->labels('Yes', 'No')->sortable(),
            DatetimeColumn::make('created_at', 'Created', 'Y-m-d H:i')->sortable(),
            EmailColumn::make('contact_email', 'Email')->sortable()->collapsedByDefault(),
            UrlColumn::make('website', 'Website')->sortable()->collapsedByDefault(),
            ColorColumn::make('accent', 'Accent')->sortable()->collapsedByDefault(),
            ToggleColumn::make('notifications', 'Alerts')->sortable()->collapsedByDefault(),
        )
            ->withFilters(
                TextFilter::make('title', 'Title contains'),
                SelectFilter::make('status', [
                    'draft' => 'Draft',
                    'published' => 'Published',
                ], 'Status'),
            )
            ->withGlobalSearch(['title', 'slug'], 'Search rows', 'search')
            ->withActions(
                ModalRowAction::include(
                    'Preview',
                    'Row preview',
                    'admin/showcase/row_preview.twig',
                    static fn (string $slug, array $row): array => ['row' => $row],
                ),
                LinkRowAction::toRoute('Dashboard', 'admin.dashboard', []),
            )
            ->withEmptyMessage('No demo rows match the current filters.');
    }

    /**
     * @param array<string, mixed> $queryString
     * @return array{perPage: int, options: list<int>}
     */
    private static function resolveShowcasePerPage(array $queryString): array
    {
        $options = [10, 15, 25];
        $default = 10;
        $perPage = $default;
        if (isset($queryString['per_page'])) {
            $pp = $queryString['per_page'];
            if (is_string($pp) && ctype_digit($pp)) {
                $v = (int) $pp;
                if (in_array($v, $options, true)) {
                    $perPage = $v;
                }
            }
        }

        return ['perPage' => $perPage, 'options' => $options];
    }

    /**
     * @param array<string, mixed> $queryString
     * @return array{
     *     uiKey: string|null,
     *     dir: string,
     *     persist: bool,
     * }
     */
    private static function resolveShowcaseSort(Table $table, array $queryString): array
    {
        $reqKey = isset($queryString['sort']) && is_string($queryString['sort']) ? $queryString['sort'] : '';
        $reqDirRaw = isset($queryString['sort_dir']) && is_string($queryString['sort_dir'])
            ? strtolower($queryString['sort_dir'])
            : 'asc';
        $reqDir = $reqDirRaw === 'desc' ? 'desc' : 'asc';

        if ($reqKey !== '') {
            foreach ($table->columns() as $col) {
                if ($col->name !== $reqKey) {
                    continue;
                }
                $db = $col->sortDatabaseColumn();
                if ($db !== null && SqlIdentifier::isSafe($db)) {
                    return [
                        'uiKey' => $col->name,
                        'dir' => $reqDir,
                        'persist' => true,
                    ];
                }

                break;
            }
        }

        return [
            'uiKey' => null,
            'dir' => 'asc',
            'persist' => false,
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function buildRowPayload(Table $table, array $row): array
    {
        $out = [];
        foreach ($table->columns() as $col) {
            $raw = $col->resolveRowValue($row);
            $out[$col->name] = $col->formatCellValue($raw);
        }
        if (! array_key_exists('id', $out) && array_key_exists('id', $row)) {
            $out['id'] = $row['id'];
        }

        return $out;
    }
}
