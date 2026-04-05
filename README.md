# vortexphp/admin

Admin UI foundation for [Vortex](https://github.com/vortexphp/framework) apps: same integration pattern as **vortexphp/live**.

## Requirements

- PHP 8.2+
- **vortexphp/framework** ^0.12 (application `Package` support, `publish:assets`, Twig loader paths)

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

This copies **`resources/admin.css`** → **`public/css/admin.css`** (see **`AdminPackage::publicAssets()`**).

## Resources (Filament-style CRUD)

1. Add **`config/admin.php`**:

```php
<?php

declare(strict_types=1);

return [
    'resources' => [
        App\Admin\Resources\PostResource::class,
    ],
];
```

2. Implement a resource class extending **`Vortex\Admin\Resource`**:

```php
<?php

declare(strict_types=1);

namespace App\Admin\Resources;

use App\Models\Post;
use Vortex\Admin\Resource;

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

    // Optional: override label(), pluralLabel(), tableColumns(), formAttributes(),
    // excludedFromTable(), excludedFromForm()
}
```

3. Your **`Model`** must use **`$fillable`** (or an empty fillable means “all attributes” for mass assignment — use explicit lists in production).

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

**Defaults**

- **Index columns:** `id` + fillable attributes (minus **`excludedFromTable()`**), capped.
- **Form fields:** fillable minus **`excludedFromForm()`** (drops **`password`**, **`remember_token`**, **`api_token`** by default).
- Long text: fields named like **`body`**, **`content`**, **`description`**, … render as `<textarea>` in the generic form template.

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
