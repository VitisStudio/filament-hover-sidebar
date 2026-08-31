# filament-hover-sidebar

Build spec for a standalone Filament v5 package: an icon-only sidebar that expands on hover,
flies out over the content instead of pushing it, and can be pinned open as a normal drawer.

Verified against **filament/filament v5.7.6**, Livewire v4, Laravel v13, PHP 8.2+.

Deliberately **not** part of `vitisstudio/filament-custom-layout` — that package is a
record-page content contract (regions, presenters, descriptors); this one is panel chrome.
No shared abstraction, disjoint install surface, and the CSS here overrides core internals so
it carries the fragile half of the version risk. Ship a thin preset package later if you want
one opinionated "HubSpot portal" install that pulls both.

---

## Why this needs writing at all

| Behaviour | Filament v5.7.6 |
| --- | --- |
| Icon-only collapsed rail | ✅ `->sidebarCollapsibleOnDesktop()` |
| Flyout menu off a nav group | ✅ free — a group with `->icon()` renders a dropdown beside the collapsed rail |
| Slide-out drawer + backdrop | ✅ mobile only |
| Hover-to-expand | ❌ no API |
| Expand without pushing content | ❌ desktop sidebar is `lg:sticky`, so it is in the flex flow |

No existing plugin covers it. [UI Plus](https://filamentphp.com/plugins/leek-ui-plus) animates the
width transition but still pushes and has no hover.

## The three facts this design rests on

1. **One flag drives everything.** `$store.sidebar.isOpen` gates labels, badges, the logo, global
   search, group headers, the item tooltips (via `x-effect`), and whether a nav group renders as
   an inline list or a dropdown. Flip that flag and the entire sidebar becomes internally
   consistent for free — no forked Blade view, correct `aria-expanded`, tooltips suppressed while
   expanded.
2. **`--collapsed-sidebar-width` is emitted but never consumed** by core CSS in v5.7.6. The
   collapsed rail is content-sized. The variable is yours to claim.
3. **Filament's CSS lives in `@layer components`.** Anything you ship unlayered beats it
   regardless of specificity, so no `!important` arms race.

Two other free hooks: `.fi-main-ctn` receives the class `fi-main-ctn-sidebar-open` bound to
`isOpen` (unstyled in core), and `.fi-layout` is the flex row holding only the sidebar and main
column — the topbar sits above it at full width, so reserving rail space on `.fi-main-ctn` leaves
the topbar alone.

## Design

Own the **pin** flag yourself; treat core's `isOpen` as pure presentation.

- `pinned` — your `$persist` key, the user's actual preference.
- `isOpen` — driven: `pinned || peeking`.
- Unpinned: sidebar goes `position: fixed`, `.fi-main-ctn` gets `padding-inline-start` equal to
  the collapsed width. Hover widens the sidebar over the content. Nothing reflows.
- Pinned: get out of the way entirely and let core's sticky push behaviour stand.

Ships as plain CSS and plain JS — no npm, no Tailwind build, no custom theme required.

---

## File tree

```
filament-hover-sidebar/
├── composer.json
├── src/
│   ├── FilamentHoverSidebarServiceProvider.php
│   └── HoverSidebarPlugin.php
└── resources/
    ├── dist/
    │   ├── hover-sidebar.css
    │   └── hover-sidebar.js
    └── views/
        └── pin-button.blade.php
```

---

## `composer.json`

```json
{
    "name": "vitisstudio/filament-hover-sidebar",
    "description": "Hover-to-expand flyout sidebar for Filament panels.",
    "keywords": ["laravel", "filament", "filament-plugin", "sidebar", "navigation"],
    "license": "MIT",
    "require": {
        "php": "^8.2",
        "filament/filament": "^5.0"
    },
    "autoload": {
        "psr-4": {
            "VitisStudio\\FilamentHoverSidebar\\": "src/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "VitisStudio\\FilamentHoverSidebar\\FilamentHoverSidebarServiceProvider"
            ]
        }
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

## `src/FilamentHoverSidebarServiceProvider.php`

```php
<?php

namespace VitisStudio\FilamentHoverSidebar;

use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\ServiceProvider;

class FilamentHoverSidebarServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'filament-hover-sidebar');

        FilamentAsset::register([
            Css::make('filament-hover-sidebar', __DIR__ . '/../resources/dist/hover-sidebar.css'),
            Js::make('filament-hover-sidebar', __DIR__ . '/../resources/dist/hover-sidebar.js'),
        ], 'vitisstudio/filament-hover-sidebar');
    }
}
```

`FilamentAsset::register()` is panel-agnostic, so the two files load everywhere. Nothing
activates until a panel adds the plugin — the JS no-ops without its config object, and the CSS
is gated on a `fhs` body class the plugin adds.

## `src/HoverSidebarPlugin.php`

```php
<?php

namespace VitisStudio\FilamentHoverSidebar;

use Filament\Contracts\Plugin;
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

        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_START,
            fn (): string => Blade::render(
                '<script>document.body.classList.add("fhs");window.fhsConfig = @js($config)</script>',
                ['config' => $config],
            ),
            scopes: $panel->getId(),
        );

        if (! $this->isPinnable) {
            return;
        }

        FilamentView::registerRenderHook(
            PanelsRenderHook::TOPBAR_START,
            fn (): string => view('filament-hover-sidebar::pin-button')->render(),
            scopes: $panel->getId(),
        );
    }
}
```

`BODY_START` is the right place for the config: it runs before the rest of the body parses, so
the `fhs` class lands before first paint and the CSS never flashes the wrong layout.

## `resources/views/pin-button.blade.php`

```blade
<div
    x-data="{}"
    x-cloak
    class="fhs-pin-btn-ctn"
>
    <x-filament::icon-button
        color="gray"
        icon="heroicon-o-lock-open"
        icon-size="lg"
        label="Pin sidebar open"
        x-show="! $store.fhs.pinned"
        x-on:click="$store.fhs.togglePin()"
        aria-controls="fi-main-sidebar"
    />

    <x-filament::icon-button
        color="gray"
        icon="heroicon-s-lock-closed"
        icon-size="lg"
        label="Unpin sidebar"
        x-show="$store.fhs.pinned"
        x-on:click="$store.fhs.togglePin()"
        aria-controls="fi-main-sidebar"
    />
</div>
```

## `resources/dist/hover-sidebar.js`

```js
;(() => {
    const BREAKPOINT = 1024
    const PERSIST_KEY = 'fhs.pinned'

    const config = () =>
        window.fhsConfig ?? {
            openDelay: 90,
            closeDelay: 180,
            pinnable: true,
            pinnedByDefault: false,
        }

    const isDesktopHover = () =>
        window.matchMedia(
            `(min-width: ${BREAKPOINT}px) and (hover: hover) and (pointer: fine)`,
        ).matches

    document.addEventListener('alpine:init', () => {
        if (!window.fhsConfig) {
            return
        }

        window.Alpine.store('fhs', {
            pinned: window.Alpine.$persist(config().pinnedByDefault).as(PERSIST_KEY),

            peeking: false,

            timer: null,

            // Core's sidebar reads a single `isOpen` flag for labels, tooltips, aria and
            // group dropdown-vs-inline. Driving it from here keeps all of that consistent
            // without touching a Blade view.
            sync() {
                const sidebar = window.Alpine.store('sidebar')
                const open = this.pinned || this.peeking

                document.body.classList.toggle('fhs-pinned', this.pinned)

                if (isDesktopHover()) {
                    sidebar.isOpen = open
                }
            },

            peek(open) {
                if (this.pinned || !isDesktopHover()) {
                    return
                }

                clearTimeout(this.timer)

                this.timer = setTimeout(
                    () => {
                        this.peeking = open
                        this.sync()
                    },
                    open ? config().openDelay : config().closeDelay,
                )
            },

            togglePin() {
                this.pinned = !this.pinned
                this.peeking = false
                this.sync()
            },
        })
    })

    const bind = () => {
        const sidebar = document.getElementById('fi-main-sidebar')

        if (!sidebar || sidebar.dataset.fhsBound) {
            return
        }

        sidebar.dataset.fhsBound = '1'

        const store = window.Alpine.store('fhs')

        sidebar.addEventListener('mouseenter', () => store.peek(true))
        sidebar.addEventListener('mouseleave', () => store.peek(false))
        sidebar.addEventListener('focusin', () => store.peek(true))
        sidebar.addEventListener('focusout', (event) => {
            if (!sidebar.contains(event.relatedTarget)) {
                store.peek(false)
            }
        })
    }

    document.addEventListener('alpine:initialized', () => {
        if (!window.fhsConfig) {
            return
        }

        window.Alpine.store('fhs').sync()
        bind()

        // Livewire SPA navigation can replace the sidebar node.
        document.addEventListener('livewire:navigated', () => {
            window.Alpine.store('fhs').sync()
            bind()
        })

        window
            .matchMedia(`(min-width: ${BREAKPOINT}px)`)
            .addEventListener('change', () => window.Alpine.store('fhs').sync())
    })
})()
```

Ordering matters and is handled: Filament registers `$store.sidebar` inside its own
`alpine:init` listener, so reading it is only safe from `alpine:initialized`. Registering
`$store.fhs` on `alpine:init` keeps the Blade bindings valid from the first render.

## `resources/dist/hover-sidebar.css`

```css
/*
 * Unlayered on purpose. Filament ships its own rules in `@layer components`, and unlayered
 * declarations beat layered ones regardless of specificity — no !important needed.
 */

.fhs-pin-btn-ctn {
    display: none;
    width: 2.25rem;
    flex-shrink: 0;
    margin-inline-end: 0.5rem;
}

@media (min-width: 1024px) and (hover: hover) and (pointer: fine) {
    body.fhs .fhs-pin-btn-ctn {
        display: block;
    }

    /* Core's desktop collapse chevrons toggle `isOpen` directly, which fights the peek. */
    body.fhs .fi-topbar-open-collapse-sidebar-btn,
    body.fhs .fi-topbar-close-collapse-sidebar-btn,
    body.fhs .fi-sidebar-open-collapse-sidebar-btn,
    body.fhs .fi-sidebar-close-collapse-sidebar-btn {
        display: none;
    }

    body.fhs.fi-body-has-sidebar-collapsible-on-desktop:not(.fhs-pinned) {
        /* Out of the flex flow, so widening it overlays instead of reflowing. */
        .fi-sidebar {
            position: fixed;
            width: var(--collapsed-sidebar-width);
            background-color: var(--fhs-sidebar-bg, #ffffff);
            transition: width var(--fhs-duration, 150ms) ease;
        }

        .fi-sidebar.fi-sidebar-open {
            width: var(--sidebar-width);
            box-shadow:
                0 10px 30px -12px rgb(0 0 0 / 0.25),
                0 0 0 1px rgb(0 0 0 / 0.05);
        }

        /* Core reserves gutter space unconditionally; at 4.5rem that clips the icons. */
        .fi-sidebar:not(.fi-sidebar-open) .fi-sidebar-nav {
            scrollbar-gutter: auto;
        }

        .fi-main-ctn {
            padding-inline-start: var(--collapsed-sidebar-width);
        }
    }

    .dark body.fhs.fi-body-has-sidebar-collapsible-on-desktop:not(.fhs-pinned) .fi-sidebar {
        background-color: var(--fhs-sidebar-bg-dark, #111827);
    }
}

@media (prefers-reduced-motion: reduce) {
    body.fhs .fi-sidebar {
        transition: none;
    }
}
```

---

## Wiring it up

```php
use VitisStudio\FilamentHoverSidebar\HoverSidebarPlugin;

return $panel
    ->sidebarWidth('17rem')
    ->collapsedSidebarWidth('4.5rem')
    ->plugin(
        HoverSidebarPlugin::make()
            ->openDelay(90)
            ->closeDelay(180)
    );
```

`sidebarCollapsibleOnDesktop()` is applied by the plugin's `register()`, so you do not need it.

Local install while developing:

```json
"repositories": [
    { "type": "path", "url": "../filament-hover-sidebar" }
]
```

```bash
composer require vitisstudio/filament-hover-sidebar:@dev
php artisan filament:assets
```

`filament:assets` must be rerun after any edit to the two `resources/dist` files — Filament
copies them into `public/js` and `public/css`.

## What you get

- **Hover-to-expand** — unpinned rail widens after `openDelay`, collapses after `closeDelay`.
- **Flyout** — the expanded sidebar is `position: fixed` over the content; `.fi-main-ctn` keeps
  its collapsed-width padding, so nothing reflows and no table re-lays-out mid-hover.
- **Drawer** — the pin button switches to core's sticky push behaviour, persisted per browser.
- **Group flyout menus** — free from core: give a `NavigationGroup` an `->icon()` and its items
  appear in a dropdown beside the collapsed rail.

## Gotchas worth knowing before you debug them

- **Collapsed width.** Core's intrinsic collapsed rail is ~5.4rem: `1.5rem` nav padding each
  side, less the `-0.5rem` group margin, plus a `2.5rem` icon button, plus `scrollbar-gutter:
  stable`. The CSS above drops the gutter so the documented `4.5rem` default actually fits. Go
  below that and icons clip against `.fi-sidebar-nav`'s `overflow-x: hidden`.
- **Long labels** clip during the width transition for the same reason. Either accept it or add
  `overflow-x: clip` with a wider padding box.
- **Touch devices.** Everything is behind `(hover: hover) and (pointer: fine)`. Touch has no
  `mouseleave`, so an ungated build leaves the sidebar stuck open.
- **`isOpen` still persists.** Core `$persist`es it under `isOpen` / `isOpenDesktop`, so peeking
  writes localStorage on every hover. Harmless — `sync()` overwrites from `fhs.pinned` on boot —
  but do not treat those keys as meaningful once this plugin is installed.
- **Do not** try the CSS-only version (`display: block !important` over `x-show`). Alpine still
  believes the sidebar is collapsed, so tooltips fire over the now-visible labels,
  `aria-expanded` reports `false`, and icon'd groups stay stuck in dropdown mode.
- **Version risk lives here.** This overrides `.fi-sidebar` positioning and hides core buttons by
  class name. Pin `filament/filament` and re-check
  `vendor/filament/filament/resources/css/components/sidebar.css` on minor upgrades.

## Testing

Behaviour is browser-level; Pest v4 browser tests are the only meaningful coverage.

```php
it('expands the sidebar on hover without moving the content', function () {
    $page = visit('/admin');

    $before = $page->script('document.querySelector(".fi-main-ctn").getBoundingClientRect().left');

    $page->hover('#fi-main-sidebar')
        ->waitForText('Dashboard')
        ->assertScript(
            'document.querySelector(".fi-main-ctn").getBoundingClientRect().left',
            $before,
        );
});
```

Assert against the *content* box, not the sidebar's — the sidebar moving is the feature, the
content staying put is the contract.

## Upstream references

- `vendor/filament/filament/resources/js/stores/sidebar.js` — the `isOpen` store
- `vendor/filament/filament/resources/css/components/sidebar.css` — `lg:sticky`, widths
- `vendor/filament/filament/resources/views/components/sidebar/item.blade.php` — tooltip `x-effect`
- `vendor/filament/filament/resources/views/components/sidebar/group.blade.php` — group dropdown flyout
- `vendor/filament/filament/src/Panel/Concerns/HasSidebar.php` — width + collapsible API
- `vendor/filament/filament/src/View/PanelsRenderHook.php` — hook constants
