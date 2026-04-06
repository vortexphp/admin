# Changelog

All notable changes to **vortexphp/admin** are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

### Changed

- **Breaking: admin pages** — **`config/admin.php`** **`pages`** is now a **list of `AdminPage` class names** (like **`resources`**). **`page_discover`** (default **`true`**) scans **`app/Admin/Pages`** via **`PageDiscovery`**. Old **`pages`** rows (`id`, **`path`**, **`action`**, …) and app HTTP controllers for those routes are removed; use **`AdminPage`** + Twig instead. **`ResourceRegistry`** skips a resource when an **`AdminPage`** already uses the same **slug**.
- **Navigation**: Pages sidebar rows use **`slug`** (not **`id`**); optional **`description`** is the link **`title`** and header subheading via **`pageDescription`**.

### Added

- **`make:admin-resource`** CLI (**`MakeAdminResourceCommand`**, registered in **`AdminPackage::console()`**): scaffold **`App\Admin\Resources\{Model}Resource`** from **`App\Models\{Model}`** (or an FQCN) using **`$fillable`** + **`$casts`**; **`--slug`**, **`--force`**. Internals: **`Codegen\ModelInspector`**, **`Codegen\ResourceScaffolder`**, stub **`resources/stubs/admin_resource.stub`**.
- **`make:admin-page`** CLI: scaffold **`App\Admin\Pages\{Name}Page`**, Twig under **`resources/views/admin/pages/`**; **`--slug`**, **`--label`**, **`--description`**, **`--icon`**, **`--hidden`**, **`--no-view`**, **`--force`**.
- **`AdminPage`**, **`AdminPageController`**, **`PageDiscovery`**, **`AdminPageRegistry`** (class registry + routes + sidebar; table showcase still appended after app pages).
- **Table showcase**: `GET /admin/showcase/tables` (`admin.showcase.tables`), **ShowcaseController**, **ShowcaseTableData** (demo rows + in-memory filters for `Table::records()`), **`LinkRowAction`** (named-route link row action), **`GlobalSearchFilter::searchColumns()`**, shared Twig **`admin/partials/resource_index_table.twig`** + **`tableListUrl`** on resource index.
- **`Table::records(callable)`**: index rows from a callback (list or id-keyed map); in-memory sort and pagination; SQL filters / global search are not applied. **`CustomTableRecords`**, **`ArrayIndexPaginator`**. **`TableColumn::resolveRowValue()`** accepts arrays for static rows (including **`BelongsToColumn`** / **`BelongsToImageColumn`** via keyed values).
- **`ModalRowAction`**: index row action **`modal`** opens **`AdmModal`** / **`admin.modal.js`**. **`content.type`** is **`form`** (field specs or trusted inner HTML + POST), **`html`** (trusted markup, optional footer), or **`include`** (Twig partial + **`with`** context). Assets: **`admin.modal.js`**, **`modal_shell.twig`**, **`modal_row_content.twig`**, **`modal_form_fields.twig`**.
- **`UploadField`**, **`MarkdownField`** (EasyMDE), **`HtmlField`** (Quill), **`TagsField`** (Tagify), **`ToggleField`**; **`Form::requiresMultipart()`**, **`Form::richEditorAssets()`**; **`layout` scripts** block + **`form_rich_assets.twig`** (CDN).
- Index **column** types in **`Tables/Columns/`** (**`TextColumn`**, **`NumericColumn`**, **`BooleanColumn`**, **`DatetimeColumn`**, **`EmailColumn`**, **`UrlColumn`**, **`BadgeColumn`**) with Twig cell partials **`admin/resource/cells/`**; **`TableColumn`** is abstract.
- Form input types: **`PasswordField`**, **`EmailField`**, **`NumberField`**, **`HiddenField`**, **`CheckboxField`**, **`SelectField`**, **`DateField`** with partials **`admin/resource/fields/`**; **`FormField::toViewArray()`** / **`normalizeRequestValue()`**.
- Dashboard **`Widget`** types (`TextWidget`, `StatsGridWidget`, `LinkListWidget`, `ResourceLinksWidget`, `NoticeWidget`, `AdminOverviewStatsWidget`) and mutable **`DashboardWidgets`** singleton; `/admin` renders them via `admin/widgets/{kind}.twig`.
- Injectable **`Navigation`** / **`NavLink`** / **`NavGroup`** (container singleton): header links with optional **`icon`** / **`iconClass`**, and grouped sections.
- Tailwind-based admin stylesheet: `resources/admin.src.css`, `npm run build` → `resources/admin.css`; Twig layouts use utility classes.
- Configurable index row actions: `TableRowAction`, `EditRowAction`, `DeleteRowAction`, `Table::withActions()`.
- Index table filters: `TextFilter`, `SelectFilter`, `Table::withFilters()`, query keys `f_*`.
- Pagination via `QueryBuilder::paginate()`, `page` and `per_page` query params.
- Resource overrides: `tablePerPage()`, `tablePerPageOptions()` for page size defaults and dropdown.
- Explicit forms: `Form`, `FormField` subclasses, `Resource::form()`.
- Index table definition: `Table`, `TableColumn` (abstract) + `Tables/Columns/*`, `Resource::table()`.
- `ResourceRegistry` + `config/admin.php` for Filament-style resources; **`admin.discover`** auto-registers `Resource` classes under configured paths (default `true` → **`app/Admin/Resources`**).
- `AdminPackage` (routes, Twig paths, `publish:assets` for CSS).
