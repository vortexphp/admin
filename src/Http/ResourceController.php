<?php

declare(strict_types=1);

namespace Vortex\Admin\Http;

use Vortex\Admin\Forms\Form;
use Vortex\Admin\Forms\FormField;
use Vortex\Admin\Forms\UploadField;
use Vortex\Admin\Resource;
use Vortex\Admin\ResourceRegistry;
use Vortex\Admin\Support\PublicAssetUrl;
use Vortex\Admin\SqlIdentifier;
use Vortex\Admin\Tables\CustomTableRecords;
use Vortex\Admin\Tables\Table;
use Vortex\Admin\Tables\TableColumn;
use Vortex\Database\Model;
use Vortex\Http\Csrf;
use Vortex\Http\Request;
use Vortex\Http\Response;
use Vortex\Http\Session;
use Vortex\Support\UrlHelp;
use JsonException;

final class ResourceController extends AdminHttpController
{
    public function index(string $slug): Response
    {
        $class = ResourceRegistry::classForSlug($slug);
        if ($class === null) {
            return Response::make('Not found', 404);
        }
        $modelClass = $class::model();
        $table = $class::table();
        $query = $modelClass::query();
        $queryString = Request::hasCurrent() ? Request::query() : [];
        $filterValues = [];
        $useCustomRecords = $table->recordsProvider() !== null;
        foreach ($table->filters() as $filter) {
            $param = $filter->queryParam();
            $raw = $queryString[$param] ?? '';
            if (is_array($raw)) {
                continue;
            }
            $filterValues[$param] = is_string($raw) ? $raw : (string) $raw;
            if (! $useCustomRecords) {
                $filter->apply($query, $filterValues[$param]);
            }
        }

        $page = 1;
        if (isset($queryString['page'])) {
            $p = $queryString['page'];
            if (is_string($p) && ctype_digit($p)) {
                $page = max(1, (int) $p);
            }
        }

        $perPageResolution = self::resolveTablePerPage($class, $queryString);
        $perPage = $perPageResolution['perPage'];
        $perPageOptions = $perPageResolution['options'];

        $sortState = self::resolveTableSort($class, $table, $queryString);

        $listQuery = $filterValues;
        if (count($perPageOptions) > 1) {
            $listQuery['per_page'] = (string) $perPage;
        }
        if ($sortState['persist'] && $sortState['uiKey'] !== null) {
            $listQuery['sort'] = $sortState['uiKey'];
            $listQuery['sort_dir'] = $sortState['dir'];
        }
        $listPath = UrlHelp::withQuery(
            route('admin.resource.index', ['slug' => $slug]),
            $listQuery,
        );

        if ($useCustomRecords) {
            $provider = $table->recordsProvider();
            $raw = $provider !== null ? $provider() : [];
            $normalized = CustomTableRecords::normalize($raw);
            $sorted = CustomTableRecords::sort($normalized, $sortState['uiKey'], $sortState['dir']);
            $total = count($sorted);
            $offset = ($page - 1) * $perPage;
            $pageRows = array_slice($sorted, $offset, $perPage);
            $records = [];
            foreach ($pageRows as $row) {
                $records[] = $this->buildRowPayload($table, $row);
            }
            $paginator = new ArrayIndexPaginator($total, $perPage, $page, $listPath);
        } else {
            $withPaths = self::uniqueEagerPaths([...$table->eagerRelationPaths(), ...$class::indexQueryWith()]);
            if ($withPaths !== []) {
                $query->with($withPaths);
            }

            $class::modifyIndexQuery($query);

            if ($sortState['apply'] !== null) {
                [$orderCol, $orderDir] = $sortState['apply'];
                $query->orderBy($orderCol, $orderDir);
            }

            $paginator = $query->paginate($page, $perPage);
            $paginator = $paginator->withBasePath($listPath, 'page');

            $records = [];
            /** @var list<Model> $rows */
            $rows = $paginator->items;
            foreach ($rows as $row) {
                $records[] = $this->buildRowPayload($table, $row);
            }
        }

        $tableRowActions = [];
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

        $indexBaseUrl = route('admin.resource.index', ['slug' => $slug]);
        $tableColumnsView = [];
        foreach ($table->columns() as $col) {
            $meta = $col->toViewArray();
            $sortDb = $col->sortDatabaseColumn();
            if ($sortDb !== null && SqlIdentifier::isSafe($sortDb)) {
                $isActive = $sortState['uiKey'] === $col->name;
                $nextDir = ($isActive && $sortState['dir'] === 'asc') ? 'desc' : 'asc';
                $meta['sortUrl'] = UrlHelp::withQuery($indexBaseUrl, array_merge($listQuery, [
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

        return $this->adminView('admin.resource.index', [
            'title' => $class::pluralLabel(),
            'slug' => $slug,
            'tableListUrl' => $indexBaseUrl,
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

    /**
     * @param class-string<Resource> $resourceClass
     * @param array<string, mixed> $queryString
     * @return array{
     *     apply: array{0: string, 1: string}|null,
     *     uiKey: string|null,
     *     dir: string,
     *     persist: bool,
     * }
     */
    private static function resolveTableSort(string $resourceClass, Table $table, array $queryString): array
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
                        'apply' => [$db, $reqDir === 'desc' ? 'DESC' : 'ASC'],
                        'uiKey' => $col->name,
                        'dir' => $reqDir,
                        'persist' => true,
                    ];
                }

                break;
            }
        }

        $default = $resourceClass::defaultTableSort();
        if ($default !== null) {
            $db = isset($default['column']) && is_string($default['column']) ? $default['column'] : '';
            $dRaw = isset($default['direction']) && is_string($default['direction'])
                ? strtolower($default['direction'])
                : 'asc';
            $d = $dRaw === 'desc' ? 'desc' : 'asc';
            if ($db !== '' && SqlIdentifier::isSafe($db)) {
                $uiKey = null;
                foreach ($table->columns() as $col) {
                    if ($col->sortDatabaseColumn() === $db) {
                        $uiKey = $col->name;
                        break;
                    }
                }

                return [
                    'apply' => [$db, $d === 'desc' ? 'DESC' : 'ASC'],
                    'uiKey' => $uiKey,
                    'dir' => $d,
                    'persist' => false,
                ];
            }
        }

        return [
            'apply' => null,
            'uiKey' => null,
            'dir' => 'asc',
            'persist' => false,
        ];
    }

    /**
     * @param class-string<\Vortex\Admin\Resource> $resourceClass
     * @param array<string, string> $queryString
     * @return array{perPage: int, options: list<int>}
     */
    private static function resolveTablePerPage(string $resourceClass, array $queryString): array
    {
        $opts = [];
        foreach ($resourceClass::tablePerPageOptions() as $n) {
            if (is_int($n)) {
                $c = max(1, min(100, $n));
                $opts[$c] = true;
            }
        }
        /** @var list<int> $options */
        $options = array_keys($opts);
        sort($options);
        if ($options === []) {
            $options = [15];
        }
        $default = max(1, min(100, $resourceClass::tablePerPage()));
        if (! in_array($default, $options, true)) {
            $options[] = $default;
            sort($options);
        }
        $perPage = $default;
        if (isset($queryString['per_page'])) {
            $pp = $queryString['per_page'];
            if (is_string($pp) && ctype_digit($pp)) {
                $v = max(1, min(100, (int) $pp));
                if (in_array($v, $options, true)) {
                    $perPage = $v;
                }
            }
        }

        return ['perPage' => $perPage, 'options' => $options];
    }

    /**
     * @param list<FormField> $fields
     * @param array<string, mixed> $values
     * @return array<string, string> upload field name => URL suitable for browsers (absolute when {@code app.url} is set)
     */
    private static function uploadFieldPublicUrls(array $fields, array $values): array
    {
        $out = [];
        foreach ($fields as $f) {
            if (! $f instanceof UploadField) {
                continue;
            }
            $raw = $values[$f->name] ?? null;
            if (! is_string($raw) || $raw === '') {
                continue;
            }
            $url = PublicAssetUrl::forImgSrc($raw);
            if ($url !== '') {
                $out[$f->name] = $url;
            }
        }

        return $out;
    }

    /**
     * @param list<string> $paths
     * @return list<string>
     */
    private static function uniqueEagerPaths(array $paths): array
    {
        $seen = [];
        foreach ($paths as $p) {
            if (is_string($p) && $p !== '') {
                $seen[$p] = true;
            }
        }

        return array_keys($seen);
    }

    public function create(string $slug): Response
    {
        $class = ResourceRegistry::classForSlug($slug);
        if ($class === null) {
            return Response::make('Not found', 404);
        }

        $form = $class::form();
        $values = $class::formValues(null);

        return $this->adminView('admin.resource.form', [
            'title' => 'Create ' . $class::label(),
            'slug' => $slug,
            'formFields' => array_map(
                static fn (FormField $f): array => $f->toViewArray(),
                $form->fields(),
            ),
            'formMultipart' => $form->requiresMultipart(),
            'formRichEditors' => $form->richEditorAssets(),
            'values' => $values,
            'uploadPublicUrls' => self::uploadFieldPublicUrls($form->fields(), $values),
            'record' => null,
            'csrfToken' => Csrf::token(),
        ]);
    }

    public function store(string $slug): Response
    {
        $class = ResourceRegistry::classForSlug($slug);
        if ($class === null) {
            return Response::make('Not found', 404);
        }
        if (! Csrf::validate()) {
            return Response::redirect(route('admin.resource.create', ['slug' => $slug]), 302);
        }

        $modelClass = $class::model();
        $payload = $this->formPayload($class::form(), null);
        $modelClass::create($payload);
        Session::flash('admin_success', 'Created.');

        return Response::redirect(route('admin.resource.index', ['slug' => $slug]), 302);
    }

    public function edit(string $slug, string $id): Response
    {
        $class = ResourceRegistry::classForSlug($slug);
        if ($class === null) {
            return Response::make('Not found', 404);
        }
        $modelClass = $class::model();
        $record = $modelClass::find($id);
        if ($record === null) {
            return Response::make('Not found', 404);
        }

        $form = $class::form();
        $values = $class::formValues($record);

        return $this->adminView('admin.resource.form', [
            'title' => 'Edit ' . $class::label(),
            'slug' => $slug,
            'formFields' => array_map(
                static fn (FormField $f): array => $f->toViewArray(),
                $form->fields(),
            ),
            'formMultipart' => $form->requiresMultipart(),
            'formRichEditors' => $form->richEditorAssets(),
            'values' => $values,
            'uploadPublicUrls' => self::uploadFieldPublicUrls($form->fields(), $values),
            'record' => $record,
            'csrfToken' => Csrf::token(),
        ]);
    }

    public function update(string $slug, string $id): Response
    {
        $class = ResourceRegistry::classForSlug($slug);
        if ($class === null) {
            return Response::make('Not found', 404);
        }
        if (! Csrf::validate()) {
            return Response::redirect(route('admin.resource.edit', ['slug' => $slug, 'id' => $id]), 302);
        }

        $modelClass = $class::model();
        $record = $modelClass::find($id);
        if ($record === null) {
            return Response::make('Not found', 404);
        }

        $payload = $this->formPayload($class::form(), $record);
        $record->update($payload);
        Session::flash('admin_success', 'Saved.');

        return Response::redirect(route('admin.resource.index', ['slug' => $slug]), 302);
    }

    public function destroy(string $slug, string $id): Response
    {
        $class = ResourceRegistry::classForSlug($slug);
        if ($class === null) {
            return Response::make('Not found', 404);
        }
        if (! Csrf::validate()) {
            return Response::redirect(route('admin.resource.index', ['slug' => $slug]), 302);
        }

        $modelClass = $class::model();
        $record = $modelClass::find($id);
        if ($record !== null) {
            $record->delete();
        }
        Session::flash('admin_success', 'Deleted.');

        return Response::redirect(route('admin.resource.index', ['slug' => $slug]), 302);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRowPayload(Table $table, Model|array $row): array
    {
        $out = [];
        foreach ($table->columns() as $col) {
            $raw = $col->resolveRowValue($row);
            $out[$col->name] = $col->formatCellValue($raw);
        }
        if (! array_key_exists('id', $out)) {
            if ($row instanceof Model && isset($row->id)) {
                $out['id'] = $row->id;
            } elseif (is_array($row) && array_key_exists('id', $row)) {
                $out['id'] = $row['id'];
            }
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    private function formPayload(Form $form, ?Model $record): array
    {
        $body = Request::all();
        $out = [];
        foreach ($form->fields() as $field) {
            $k = $field->name;
            if ($field instanceof UploadField) {
                $existing = null;
                if ($record !== null) {
                    $ev = $record->{$k} ?? null;
                    $existing = is_string($ev) ? $ev : ($ev !== null && is_scalar($ev) ? (string) $ev : null);
                }
                $out[$k] = $field->normalizeUpload(Request::file($k), $existing);

                continue;
            }
            $raw = $body[$k] ?? null;
            $out[$k] = $field->normalizeRequestValue($raw);
        }

        return $out;
    }
}
