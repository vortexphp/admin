# Changelog

All notable changes to **vortexphp/admin** are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

### Added

- Dashboard **`Widget`** types (`TextWidget`, `StatsGridWidget`, `LinkListWidget`, `ResourceLinksWidget`, `NoticeWidget`, `AdminOverviewStatsWidget`) and mutable **`DashboardWidgets`** singleton; `/admin` renders them via `admin/widgets/{kind}.twig`.
- Injectable **`Navigation`** / **`NavLink`** / **`NavGroup`** (container singleton): header links with optional **`icon`** / **`iconClass`**, and grouped sections.
- Tailwind-based admin stylesheet: `resources/admin.src.css`, `npm run build` → `resources/admin.css`; Twig layouts use utility classes.
- Configurable index row actions: `TableRowAction`, `EditAction`, `DeleteAction`, `Table::withActions()`.
- Index table filters: `TextFilter`, `SelectFilter`, `Table::withFilters()`, query keys `f_*`.
- Pagination via `QueryBuilder::paginate()`, `page` and `per_page` query params.
- Resource overrides: `tablePerPage()`, `tablePerPageOptions()` for page size defaults and dropdown.
- Explicit forms: `Form`, `FormField` subclasses (`TextField`, `TextareaField`), `Resource::form()`.
- Index table definition: `Table`, `TableColumn`, `Resource::table()`.
- `ResourceRegistry` + `config/admin.php` for Filament-style resources; **`admin.discover`** auto-registers `Resource` classes under configured paths (default `true` → **`app/Admin/Resources`**).
- `AdminPackage` (routes, Twig paths, `publish:assets` for CSS).
