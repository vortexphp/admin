<?php

declare(strict_types=1);

namespace Vortex\Admin\Tests;

use PHPUnit\Framework\TestCase;
use Vortex\Admin\NavGroup;
use Vortex\Admin\NavLink;
use Vortex\Admin\Navigation;

final class NavigationTest extends TestCase
{
    public function testLinkIconsAndToViewArray(): void
    {
        $nav = Navigation::make()
            ->link('Home', '/admin', '🏠')
            ->link('Out', 'https://example.com', null, 'opacity-80')
            ->add(new NavLink('X', '/x', '📄'));

        self::assertSame([
            [
                'kind' => 'link',
                'label' => 'Home',
                'href' => '/admin',
                'icon' => '🏠',
                'iconClass' => null,
            ],
            [
                'kind' => 'link',
                'label' => 'Out',
                'href' => 'https://example.com',
                'icon' => null,
                'iconClass' => 'opacity-80',
            ],
            [
                'kind' => 'link',
                'label' => 'X',
                'href' => '/x',
                'icon' => '📄',
                'iconClass' => null,
            ],
        ], $nav->toViewArray());
    }

    public function testGroup(): void
    {
        $nav = Navigation::make()->group('Content', function (NavGroup $g): void {
            $g->link('A', '/a');
            $g->add(new NavLink('B', '/b', '⭐'));
        });

        self::assertSame([
            [
                'kind' => 'group',
                'label' => 'Content',
                'items' => [
                    [
                        'kind' => 'link',
                        'label' => 'A',
                        'href' => '/a',
                        'icon' => null,
                        'iconClass' => null,
                    ],
                    [
                        'kind' => 'link',
                        'label' => 'B',
                        'href' => '/b',
                        'icon' => '⭐',
                        'iconClass' => null,
                    ],
                ],
            ],
        ], $nav->toViewArray());
    }

    public function testNavGroupMake(): void
    {
        $g = NavGroup::make('System', function (NavGroup $g): void {
            $g->link('Settings', '/settings');
        });

        self::assertSame([
            'kind' => 'group',
            'label' => 'System',
            'items' => [
                [
                    'kind' => 'link',
                    'label' => 'Settings',
                    'href' => '/settings',
                    'icon' => null,
                    'iconClass' => null,
                ],
            ],
        ], $g->toViewArray());
    }
}
