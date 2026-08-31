<?php

namespace Workbench\App\Providers;

use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use VitisStudio\FilamentHoverSidebar\HoverSidebarPlugin;
use Workbench\App\Filament\Pages\Reports;
use Workbench\App\Filament\Pages\Settings;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->colors(['primary' => Color::Amber])
            ->sidebarWidth('17rem')
            ->collapsedSidebarWidth('4.5rem')
            ->pages([
                Dashboard::class,
                Reports::class,
                Settings::class,
            ])
            ->navigationGroups([
                // No icon: renders as an inline list whose header is gated on `isOpen`.
                NavigationGroup::make('Workspace'),
                // Icon: core renders this as a dropdown flyout beside the collapsed rail.
                NavigationGroup::make('Pipeline')->icon('heroicon-o-funnel'),
                NavigationGroup::make('Administration')->icon('heroicon-o-cog-6-tooth'),
            ])
            ->navigationItems($this->navigationItems())
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugin(
                HoverSidebarPlugin::make()
                    ->openDelay(90)
                    ->closeDelay(180)
            );
    }

    /**
     * Filler nav to exercise the parts of the sidebar that are gated on `isOpen`: labels,
     * badges, group headers, and inline-vs-dropdown group rendering.
     *
     * @return array<NavigationItem>
     */
    protected function navigationItems(): array
    {
        $items = [
            ['Contacts', 'heroicon-o-users', 'Workspace', '128'],
            ['Companies', 'heroicon-o-building-office-2', 'Workspace', null],
            ['Quarterly revenue attribution', 'heroicon-o-chart-pie', 'Workspace', null],
            ['Deals', 'heroicon-o-banknotes', 'Pipeline', '12'],
            ['Forecast', 'heroicon-o-presentation-chart-line', 'Pipeline', null],
            ['Lost opportunities archive', 'heroicon-o-archive-box-x-mark', 'Pipeline', null],
            ['Users', 'heroicon-o-user-circle', 'Administration', null],
            ['Roles & permissions', 'heroicon-o-shield-check', 'Administration', '3'],
            ['Audit log', 'heroicon-o-clipboard-document-list', 'Administration', null],
        ];

        return array_map(
            fn (array $item): NavigationItem => NavigationItem::make($item[0])
                ->icon($item[1])
                ->group($item[2])
                ->badge($item[3])
                ->url('#'),
            $items,
        );
    }
}
