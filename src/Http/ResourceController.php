<?php

declare(strict_types=1);

namespace Vortex\Admin\Http;

use Vortex\Admin\ResourceRegistry;
use Vortex\Database\Model;
use Vortex\Http\Controller;
use Vortex\Http\Csrf;
use Vortex\Http\Request;
use Vortex\Http\Response;
use Vortex\Http\Session;
use Vortex\View\View;

final class ResourceController extends Controller
{
    public function index(string $slug): Response
    {
        $class = ResourceRegistry::classForSlug($slug);
        if ($class === null) {
            return Response::make('Not found', 404);
        }
        $modelClass = $class::model();
        /** @var list<Model> $rows */
        $rows = $modelClass::all();
        $columns = $class::tableColumns();

        $records = [];
        foreach ($rows as $row) {
            $records[] = $this->rowPayload($row, $columns);
        }

        return View::html('admin.resource.index', [
            'title' => $class::pluralLabel(),
            'slug' => $slug,
            'columns' => $columns,
            'records' => $records,
            'csrfToken' => Csrf::token(),
        ]);
    }

    public function create(string $slug): Response
    {
        $class = ResourceRegistry::classForSlug($slug);
        if ($class === null) {
            return Response::make('Not found', 404);
        }

        return View::html('admin.resource.form', [
            'title' => 'Create ' . $class::label(),
            'slug' => $slug,
            'fields' => $class::formAttributes(),
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
        $payload = $this->formPayload($class::formAttributes());
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

        $fields = $class::formAttributes();
        $values = [];
        foreach ($fields as $f) {
            $values[$f] = $record->{$f} ?? '';
        }

        return View::html('admin.resource.form', [
            'title' => 'Edit ' . $class::label(),
            'slug' => $slug,
            'fields' => $fields,
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

        $payload = $this->formPayload($class::formAttributes());
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
     * @param list<string> $columns
     * @return array<string, mixed>
     */
    private function rowPayload(Model $row, array $columns): array
    {
        $out = [];
        foreach ($columns as $col) {
            $v = $row->{$col} ?? null;
            if (is_string($v) && strlen($v) > 80) {
                $v = substr($v, 0, 77) . '…';
            }
            $out[$col] = $v;
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
