<?php

declare(strict_types=1);

namespace Vortex\Admin\Http;

use Vortex\Admin\ResourceRegistry;
use Vortex\Admin\Tables\Table;
use Vortex\Database\Model;
use Vortex\Http\Csrf;
use Vortex\Http\Request;
use Vortex\Http\Response;
use Vortex\Http\Session;
use Vortex\Support\UrlHelp;

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
        foreach ($table->filters() as $filter) {
            $param = $filter->queryParam();
            $raw = $queryString[$param] ?? '';
            if (is_array($raw)) {
                continue;
            }
            $filterValues[$param] = is_string($raw) ? $raw : (string) $raw;
            $filter->apply($query, $filterValues[$param]);
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

        $paginator = $query->paginate($page, $perPage);
        $listQuery = $filterValues;
        if (count($perPageOptions) > 1) {
            $listQuery['per_page'] = (string) $perPage;
        }
        $listPath = UrlHelp::withQuery(
            route('admin.resource.index', ['slug' => $slug]),
            $listQuery,
        );
        $paginator = $paginator->withBasePath($listPath, 'page');

        $records = [];
        /** @var list<Model> $rows */
        $rows = $paginator->items;
        foreach ($rows as $row) {
            $records[] = $this->rowPayload($row, $table);
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

        return $this->adminView('admin.resource.index', [
            'title' => $class::pluralLabel(),
            'slug' => $slug,
            'tableColumns' => $table->columns(),
            'tableFilters' => $table->filters(),
            'tableActions' => $table->actions(),
            'filterValues' => $filterValues,
            'perPageOptions' => $perPageOptions,
            'tablePerPageCurrent' => $perPage,
            'pagination' => $paginator,
            'records' => $records,
            'tableRowActions' => $tableRowActions,
            'csrfToken' => Csrf::token(),
        ]);
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

    public function create(string $slug): Response
    {
        $class = ResourceRegistry::classForSlug($slug);
        if ($class === null) {
            return Response::make('Not found', 404);
        }

        return $this->adminView('admin.resource.form', [
            'title' => 'Create ' . $class::label(),
            'slug' => $slug,
            'formFields' => $class::form()->fields(),
            'values' => [],
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
        $payload = $this->formPayload($class::form()->fieldNames());
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
        $values = [];
        foreach ($form->fieldNames() as $f) {
            $values[$f] = $record->{$f} ?? '';
        }

        return $this->adminView('admin.resource.form', [
            'title' => 'Edit ' . $class::label(),
            'slug' => $slug,
            'formFields' => $form->fields(),
            'values' => $values,
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

        $payload = $this->formPayload($class::form()->fieldNames());
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
    private function rowPayload(Model $row, Table $table): array
    {
        $out = [];
        foreach ($table->columnNames() as $col) {
            $v = $row->{$col} ?? null;
            if (is_string($v) && strlen($v) > 80) {
                $v = substr($v, 0, 77) . '…';
            }
            $out[$col] = $v;
        }
        if (! array_key_exists('id', $out) && isset($row->id)) {
            $out['id'] = $row->id;
        }

        return $out;
    }

    /**
     * @param list<string> $keys
     * @return array<string, mixed>
     */
    private function formPayload(array $keys): array
    {
        $body = Request::all();
        $out = [];
        foreach ($keys as $k) {
            if (! array_key_exists($k, $body)) {
                continue;
            }
            $out[$k] = is_string($body[$k]) ? trim($body[$k]) : $body[$k];
        }

        return $out;
    }
}
