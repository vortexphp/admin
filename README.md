# vortexphp/admin

Admin UI foundation for [Vortex](https://github.com/vortexphp/framework) apps: same integration pattern as **vortexphp/live**.

## Requirements

- PHP 8.2+
- **vortexphp/framework** ^0.12 (application `Package` support, `publish:assets`, Twig loader paths)
- **Tailwind** (optional for consumers): the published **`resources/admin.css`** is pre-built. To change admin styles, use **Node 18+**, run **`npm install`** and **`npm run build`** in this package (see **`package.json`**).

## Install

```bash
composer require vortexphp/admin
```

In **`config/app.php`**:

```php
'packages' => [
    \Vortex\Live\LivePackage::class,
    \Vortex\Admin\AdminPackage::class,
],
```

Publish static assets:

```bash
php vortex publish:assets
```

This copies **`resources/admin.css`** → **`public/css/admin.css`** (see **`AdminPackage::publicAssets()`**). That file is generated from **`resources/admin.src.css`** via Tailwind (**`npm run build`** in **`vortexphp/admin`**).

## Navigation (header links)

**`Vortex\Admin\Navigation`** is registered as a container singleton. In your app **`Package::boot()`** (after admin routes exist), register entries:

```php
$nav = $container->make(\Vortex\Admin\Navigation::class);

$nav->link('Posts', route('admin.resource.index', ['slug' => 'posts']), icon: '📝');
$nav->link('Site', '/', iconClass: 'size-4 opacity-80'); // decorative span classes (icon font / SVG mask / etc.)

$nav->group('Content', function (\Vortex\Admin\NavGroup $g): void {
    $g->link('Notes', route('admin.resource.index', ['slug' => 'notes']));
    $g->add(\Vortex\Admin\NavLink::route('Dashboard', 'admin.dashboard'));
});

$nav->add(\Vortex\Admin\NavGroup::make('System', function (\Vortex\Admin\NavGroup $g): void {
    $g->link('Settings', '/admin/settings');
}));
```

- **`icon`** — optional short text or emoji (escaped in Twig).
- **`iconClass`** — optional classes on an empty `<span>` for your own icon setup (include size utilities here).
- **`Navigation::group()`** / **`NavGroup`** — labeled sections in the header; groups contain **`NavLink`** rows only.

Arbitrary URLs use **`NavLink`** or **`$nav->link('Label', 'https://…')`**. Everything renders beside **Admin** on the dashboard, resource index, and resource forms.

## Dashboard widgets

**`Vortex\Admin\DashboardWidgets`** is a container singleton. The admin home (**`/admin`**, **`DashboardController`**) renders **`dashboardWidgets`**. Defaults: **`NoticeWidget`** (welcome) → **`AdminOverviewStatsWidget`** (registered resource count) → **`TextWidget`** (config hint) → **`ResourceLinksWidget`**. Replace or extend in **`Package::boot()`**:

```php
use Vortex\Admin\DashboardWidgets;
use Vortex\Admin\Widgets\LinkListWidget;
use Vortex\Admin\Widgets\NoticeTone;
use Vortex\Admin\Widgets\NoticeWidget;
use Vortex\Admin\Widgets\ResourceLinksWidget;
use Vortex\Admin\Widgets\StatsGridWidget;
use Vortex\Admin\Widgets\TextWidget;

$dash = $container->make(DashboardWidgets::class);
$dash->clear()
    ->add(new NoticeWidget(NoticeTone::Info, 'Welcome back.', 'Hello'))
    ->add(new StatsGridWidget('Today', [
        ['label' => 'Orders', 'value' => '12', 'hint' => 'since midnight'],
    ]))
    ->add(new LinkListWidget('Tools', [
        ['label' => 'Reports', 'href' => '/admin/reports', 'description' => 'CSV exports'],
    ]))
    ->add(new TextWidget(null, "Plain copy with line breaks.\nSecond line."))
    ->add(new ResourceLinksWidget('CRUD'));
```

Each widget exposes a **`kind`** (Twig partial name under **`admin/widgets/`**). Add your own by implementing **`Vortex\Admin\Widgets\Widget`** and a matching **`admin/widgets/{kind}.twig`** in your app’s Twig paths.

## Resources (Filament-style CRUD)

1. Add **`config/admin.php`**:

```php
<?php

declare(strict_types=1);

return [
    // Scan app/Admin/Resources/*.php (PSR-4 class per file from composer.json "autoload")
    'discover' => true,

    // Optional: extra classes or duplicates (explicit entries win on slug conflicts)
    'resources' => [
        // App\Admin\Resources\PostResource::class,
    ],

    // Instead of or in addition to true, use path(s) relative to the project root:
    // 'discover' => ['app/Admin/Resources', 'src/More/AdminResources'],
    // Absolute paths are allowed when needed.
];
```

2. Implement a resource class extending **`Vortex\Admin\Resource`**:

```php
<?php

declare(strict_types=1);

namespace App\Admin\Resources;

use App\Models\Post;
use Vortex\Admin\Forms\Form;
use Vortex\Admin\Forms\TextareaField;
use Vortex\Admin\Forms\TextField;
use Vortex\Admin\Resource;
use Vortex\Admin\Tables\Columns\DatetimeColumn;
use Vortex\Admin\Tables\Columns\TextColumn;
use Vortex\Admin\Tables\Table;
use Vortex\Admin\Tables\TextFilter;

final class PostResource extends Resource
{
    public static function model(): string
    {
        return Post::class;
    }

    public static function slug(): string
    {
        return 'posts'; // /admin/posts
    }

    public static function tablePerPage(): int
    {
        return 25;
    }

    /**
     * @return list<int>
     */
    public static function tablePerPageOptions(): array
    {
        return [25, 50, 100];
    }

    public static function table(): Table
    {
        return Table::make(
            TextColumn::make('id'),
            TextColumn::make('title'),
            DatetimeColumn::make('created_at', 'Created', 'Y-m-d H:i'),
        )->withFilters(
            TextFilter::make('title', 'Title'),
        );
    }

    public static function form(): Form
    {
        return Form::make(
            TextField::make('title'),
            TextareaField::make('body'),
        );
    }

    // Optional: label(), pluralLabel()
}
```

3. Your **`Model`** should list assignable attributes in **`$fillable`** to match what you persist from **`form()`** (and avoid mass-assignment surprises).

**Routes** (registered by **`AdminPackage`**):

| Method | Path | Name |
|--------|------|------|
| GET | `/admin` | `admin.dashboard` |
| GET | `/admin/{slug}` | `admin.resource.index` |
| GET | `/admin/{slug}/create` | `admin.resource.create` |
| POST | `/admin/{slug}` | `admin.resource.store` |
| GET | `/admin/{slug}/{id}/edit` | `admin.resource.edit` |
| POST | `/admin/{slug}/{id}` | `admin.resource.update` |
| POST | `/admin/{slug}/{id}/delete` | `admin.resource.destroy` |

Forms include **`_csrf`**; invalid CSRF redirects back without flash error (harden further as needed).

**Table API**

- **`Table::make(...)`** takes subclasses of **`TableColumn`** (each in its own file under **`Vortex\Admin\Tables\Columns\`**):
  - **`TextColumn::make('attr', 'Optional label', maxLength: 80)`** — default string cell; truncates long values for the grid.
  - **`NumericColumn::make('price', 'Price', decimals: 2)`** — **`->withThousandsSeparator(',')`** optional.
  - **`BooleanColumn::make('active')`** — **`->labels('Yes', 'No', '—')`** for display.
  - **`DatetimeColumn::make('created_at', 'Created', 'Y-m-d H:i')`**
  - **`EmailColumn::make('email')`**, **`UrlColumn::make('website')`** — links in the index; anchor text can truncate while `href` stays full.
  - **`BadgeColumn::make('status', 'Status', ['draft' => ['label' => 'Draft', 'tone' => 'warning'], ...])`** — pills; tones: **`neutral`**, **`success`**, **`warning`**, **`danger`**.
- Column **`displayKind()`** maps to **`resources/views/admin/resource/cells/{kind}.twig`** (add your own column class + partial to extend).
- **Filters** (optional): chain **`->withFilters(TextFilter::make('title', 'Contains'), SelectFilter::make('status', ['draft' => 'Draft'], 'Status'), ...)`**. Index uses GET query keys **`f_{column}`** (e.g. **`f_title`**). **`TextFilter`** uses **`LIKE %…%`** (wildcards in the value are escaped). **`SelectFilter`** whitelists values against its options map.
- **Row actions**: **`Table::make()`** adds **`EditRowAction`** and **`DeleteRowAction`** by default. Replace with **`->withActions(EditRowAction::make('Modify'), ...)`** or **`->withActions()`** (empty — no actions column). Implement **`TableRowAction::resolve($slug, $row)`** to return **`kind`** (`link` / `post` / `modal`), **`label`**, **`route`** name, and **`routeParams`** for custom links or POST forms (delete uses POST + CSRF in the template). See **`ModalRowAction`** for dialogs.
- **Pagination**: index uses **`QueryBuilder::paginate()`** with query **`page`**. Override **`tablePerPage(): int`** for the default page size when **`per_page`** is missing or invalid (clamped **`1…100`**). Override **`tablePerPageOptions(): array`** (list of ints) for the “Per page” dropdown; **one** value hides the control; if **`tablePerPage()`** is not in that list, it is merged in. Page links preserve **`per_page`** and filter query params when multiple sizes exist.

**Form API** (same idea as the table)

- **`Form::make(...)`** — field order; each field is its own class under **`Vortex\Admin\Forms\`**.
- Built-ins: **`TextField`**, **`TextareaField`**, **`PasswordField`**, **`EmailField`**, **`NumberField`**, **`HiddenField`**, **`CheckboxField`**, **`ToggleField`** (switch UI), **`SelectField::make('role', ['a' => 'A'], 'Label')`**, **`DateField`**, **`UploadField`** (multipart; stores under **`public/{dir}/`** via **`->to('uploads/posts')`**, optional **`->maxKb()`**, **`->allowedMimes()`**, **`->allowedExtensions()`**, **`->accept()`**, **`->discardExistingWhenEmpty()`**), **`MarkdownField`** (EasyMDE from CDN), **`HtmlField`** (Quill WYSIWYG from CDN — **sanitize HTML on output** in your app), **`TagsField`** (Tagify; **`->asJson()`** or CSV **`->delimiter(',')`**).
- Rich editors load JS/CSS from jsDelivr on resource forms only (`layout` **`{% block scripts %}`**). Internet access is required in the browser for first load (or vendor assets locally if you replace **`form_rich_assets.twig`**).
- Any **`UploadField`** sets the form’s **`enctype="multipart/form-data"`** automatically.
- Each field’s **`inputKind()`** maps to **`resources/views/admin/resource/fields/{kind}.twig`**. Implement **`FormField::toViewArray()`** / **`normalizeRequestValue()`** (and **`UploadField::normalizeUpload()`**) when you add types.
- **`ResourceController`** merges POST with **`normalizeRequestValue()`**; file fields use **`Request::file()`** and keep the previous path on edit when **`keepExistingOnEmpty`** is true.

## What it registers

| Piece | Purpose |
|-------|---------|
| **`AdminPackage::boot()`** | Package Twig views + dashboard + resource CRUD routes |
| **`DashboardController`** | Lists configured resources |
| **`ResourceController`** | Index / create / store / edit / update / destroy |
| **CSS** | Minimal dark shell (`publish:assets`) |

## Parity with vortexphp/live

| Concern | live | admin |
|---------|------|-------|
| Composer type | `library` | `library` |
| Entry class | **`LivePackage`** | **`AdminPackage`** |
| Extends | **`Vortex\Package\Package`** | same |
| **`publicAssets()`** | `live.js` → `public/js/` | `admin.css` → `public/css/` |
| **`boot()`** | Routes + Twig extension | Routes + **`Factory::addTemplatePath()`** |
| Views / JS | Package-owned | Package-owned |

Override routes or controllers in your app by registering another package later or by not loading this package and copying the pattern.
