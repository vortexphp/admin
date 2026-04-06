<?php

declare(strict_types=1);

namespace Vortex\Admin;

/**
 * Custom admin screen: class-based like {@see Resource}, auto-discovered under {@see PageDiscovery} paths
 * and/or listed in {@code config/admin.php} key {@code pages}.
 *
 * Registers {@code GET /admin/{slug}} before resource routes. Pass {@code adminPage} = {@see slug()} in views for sidebar state.
 */
abstract class AdminPage
{
    /**
     * URL segment: {@code /admin/{slug}} (use hyphens; must not collide with a {@see Resource} slug you rely on).
     */
    abstract public static function slug(): string;

    /**
     * Twig template name (e.g. {@code admin.pages.reports} → {@code admin/pages/reports.twig}).
     */
    abstract public static function view(): string;

    /**
     * Sidebar / document title.
     */
    public static function title(): string
    {
        return ucwords(str_replace(['-', '_'], ' ', static::slug()));
    }

    /**
     * Optional subtitle copy (pass to views; use in Twig or layout {@code subheading} when needed).
     */
    public static function description(): string
    {
        return '';
    }

    /**
     * When false, the page route exists but the entry is omitted from the default Pages sidebar.
     */
    public static function showInNavigation(): bool
    {
        return true;
    }

    /**
     * Icon key for {@code admin/partials/icon_svg.twig} (e.g. {@code document}, {@code table}).
     */
    public static function navigationIcon(): ?string
    {
        return null;
    }

    /**
     * Stable route name for {@code route()}: {@code admin.pages.{slug with hyphens → underscores}}.
     */
    public static function routeName(): string
    {
        return 'admin.pages.' . str_replace('-', '_', static::slug());
    }
}
