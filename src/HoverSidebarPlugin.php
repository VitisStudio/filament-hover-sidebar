<?php

namespace VitisStudio\FilamentHoverSidebar;

use Filament\Contracts\Plugin;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;

class HoverSidebarPlugin implements Plugin
{
    protected int $openDelay = 90;

    protected int $closeDelay = 180;

    protected bool $isPinnable = true;

    protected bool $isPinnedByDefault = false;

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    public function getId(): string
    {
        return 'filament-hover-sidebar';
    }

    /**
     * Milliseconds the pointer must rest on the rail before it expands. Below ~60ms the rail
     * twitches on every pointer transit across the viewport edge.
     */
    public function openDelay(int $milliseconds): static
    {
        $this->openDelay = $milliseconds;

        return $this;
    }

    public function closeDelay(int $milliseconds): static
    {
        $this->closeDelay = $milliseconds;

        return $this;
    }

    public function pinnable(bool $condition = true): static
    {
        $this->isPinnable = $condition;

        return $this;
    }

    public function pinnedByDefault(bool $condition = true): static
    {
        $this->isPinnedByDefault = $condition;

        return $this;
    }

    public function getOpenDelay(): int
    {
        return $this->openDelay;
    }

    public function getCloseDelay(): int
    {
        return $this->closeDelay;
    }

    public function isPinnable(): bool
    {
        return $this->isPinnable;
    }

    public function isPinnedByDefault(): bool
    {
        return $this->isPinnedByDefault;
    }

    public function register(Panel $panel): void
    {
        $panel->sidebarCollapsibleOnDesktop();
    }

    public function boot(Panel $panel): void
    {
        $config = [
            'openDelay' => $this->openDelay,
            'closeDelay' => $this->closeDelay,
            'pinnable' => $this->isPinnable,
            'pinnedByDefault' => $this->isPinnedByDefault,
        ];

        // Both hooks are registered unscoped. A panel-id scope would never match: core renders
        // BODY_START with the *page's* render-hook scopes (page and resource class names) and
        // TOPBAR_START with no scopes at all. `Panel::boot()` runs only for the active panel,
        // and the closures re-check it, so unscoped registration stays panel-correct anyway.
        //
        // BODY_START itself is the right position: it runs before the rest of the body parses,
        // so the `fhs` class lands before first paint and the CSS never flashes the wrong layout.
        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_START,
            fn (): string => $this->isActivePanel($panel)
                ? Blade::render(
                    '<script>document.body.classList.add("fhs");window.fhsConfig = @js($config)</script>',
                    ['config' => $config],
                )
                : '',
        );

        if (! $this->isPinnable) {
            return;
        }

        FilamentView::registerRenderHook(
            PanelsRenderHook::TOPBAR_START,
            fn (): string => $this->isActivePanel($panel)
                ? view('filament-hover-sidebar::pin-button')->render()
                : '',
        );
    }

    protected function isActivePanel(Panel $panel): bool
    {
        return Filament::getCurrentOrDefaultPanel()?->getId() === $panel->getId();
    }
}
