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

## What it registers

| Piece | Purpose |
|-------|---------|
| **`AdminPackage::boot()`** | Adds package **`resources/views`** to Twig; **`GET /admin`** → **`admin.dashboard`** |
| **`DashboardController`** | Renders **`admin.dashboard`** Twig |
| **CSS** | Minimal dark shell; replace or extend in your app |

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
