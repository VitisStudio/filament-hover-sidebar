# Filament Hover Sidebar

[![Latest Version on Packagist](https://img.shields.io/packagist/v/vitisstudio/filament-hover-sidebar.svg?style=flat-square)](https://packagist.org/packages/vitisstudio/filament-hover-sidebar)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/vitisstudio/filament-hover-sidebar/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/vitisstudio/filament-hover-sidebar/actions?query=workflow%3Atests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/vitisstudio/filament-hover-sidebar/fix-code-style.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/vitisstudio/filament-hover-sidebar/actions?query=workflow%3A%22fix+code+style%22+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/vitisstudio/filament-hover-sidebar.svg?style=flat-square)](https://packagist.org/packages/vitisstudio/filament-hover-sidebar)

An icon-only sidebar for Filament panels that expands on hover, flies out **over** the content
instead of pushing it, and can be pinned open as a normal drawer.

Filament v5 already gives you the collapsed rail (`sidebarCollapsibleOnDesktop()`) and group
flyout menus. What it has no API for is hover-to-expand, and its desktop sidebar is `lg:sticky`
— in the flex flow — so expanding it reflows the page. This plugin adds the first and fixes the
second.

Verified against **filament/filament v5.7.7**, Livewire v4, Laravel v13, PHP 8.2+.

## How it works

The plugin owns a **`pinned`** flag (an Alpine `$persist` key — the user's actual preference)
and treats core's `$store.sidebar.isOpen` as pure presentation, driving it from
`pinned || peeking`.

That one flag is what makes this small. Core gates labels, badges, the logo, global search,
group headers, item tooltips (via `x-effect`), `aria-expanded`, and whether a nav group renders
as an inline list or a dropdown — all on `isOpen`. Flipping it makes the whole sidebar
internally consistent for free, with no forked Blade view.

- **Unpinned** — the sidebar goes `position: fixed` and `.fi-main-ctn` takes a
  `padding-inline-start` equal to the collapsed width. Hover widens the sidebar over the
  content. Nothing reflows.
- **Pinned** — the plugin gets out of the way entirely and core's sticky push behaviour stands.

Ships as plain CSS and plain JS. No npm, no Tailwind build, no custom theme required.

## Installation

```bash
composer require vitisstudio/filament-hover-sidebar
php artisan filament:assets
```

Register the plugin on a panel:

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

The CSS and JS are registered panel-agnostically and load everywhere, but nothing activates
until a panel adds the plugin — the JS no-ops without its config object, and the CSS is gated on
an `fhs` body class the plugin adds.

## Configuration

| Method | Default | Purpose |
| --- | --- | --- |
| `openDelay(int $ms)` | `90` | How long the pointer must rest on the rail before it expands. Below ~60ms the rail twitches on every pointer transit across the viewport edge. |
| `closeDelay(int $ms)` | `180` | Grace period before a departing pointer collapses the rail. |
| `pinnable(bool)` | `true` | Renders the topbar pin toggle. Set `false` for hover-only. |
| `pinnedByDefault(bool)` | `false` | Initial value of the persisted `pinned` preference. |

Two CSS custom properties are available for theming, both with fallbacks:

```css
:root {
    --fhs-sidebar-bg: #ffffff;
    --fhs-sidebar-bg-dark: #111827;
    --fhs-duration: 150ms;
}
```

Translations for the pin button live in `filament-hover-sidebar::hover-sidebar` and can be
published:

```bash
php artisan vendor:publish --tag="filament-hover-sidebar-translations"
```

## What you get

- **Hover-to-expand** — the unpinned rail widens after `openDelay`, collapses after `closeDelay`.
  Keyboard `focusin`/`focusout` expand it too, so tabbing into the nav works.
- **Flyout** — the expanded sidebar is `position: fixed` over the content; `.fi-main-ctn` keeps
  its collapsed-width padding, so nothing reflows and no table re-lays-out mid-hover.
- **Drawer** — the pin button switches to core's sticky push behaviour, persisted per browser.
- **Group flyout menus** — free from core: give a `NavigationGroup` an `->icon()` and its items
  appear in a dropdown beside the collapsed rail.

## Gotchas worth knowing before you debug them

- **Collapsed width.** Core's intrinsic collapsed rail is ~5.4rem: `1.5rem` nav padding each
  side, less the `-0.5rem` group margin, plus a `2.5rem` icon button, plus
  `scrollbar-gutter: stable`. The plugin's CSS drops the gutter so the documented `4.5rem`
  default actually fits. Go below that and icons clip against `.fi-sidebar-nav`'s
  `overflow-x: hidden`.
- **Long labels** clip during the width transition for the same reason. Either accept it or add
  `overflow-x: clip` with a wider padding box.
- **Touch devices.** Everything is behind `(hover: hover) and (pointer: fine)`. Touch has no
  `mouseleave`, so an ungated build would leave the sidebar stuck open.
- **`isOpen` still persists.** Core `$persist`s it under `isOpen` / `isOpenDesktop`, so peeking
  writes localStorage on every hover. Harmless — the store overwrites from `fhs.pinned` on boot
  — but do not treat those keys as meaningful once this plugin is installed.
- **Do not** attempt a CSS-only version (`display: block !important` over `x-show`). Alpine
  still believes the sidebar is collapsed, so tooltips fire over the now-visible labels,
  `aria-expanded` reports `false`, and icon'd groups stay stuck in dropdown mode.
- **Render hooks here must be unscoped.** Scoping them to the panel id renders nothing. Core
  passes a *page's* render-hook scopes (`getRenderHookScopes()` — page and resource class names)
  to `BODY_START`, and passes no scopes at all to `TOPBAR_START`; a panel id matches neither.
  `Panel::boot()` only runs for the active panel, and the hook closures re-check
  `Filament::getCurrentOrDefaultPanel()`, so unscoped registration stays panel-correct.
- **Version risk lives here.** This overrides `.fi-sidebar` positioning and hides core buttons
  by class name. Pin `filament/filament` and re-check
  `vendor/filament/filament/resources/css/components/sidebar.css` on minor upgrades.

## Try it without an app

The package ships a Testbench workbench — a throwaway Filament panel wired to the plugin, with
no authentication, so `/admin` opens straight onto the dashboard.

```bash
composer install
composer serve
```

That builds the workbench (sqlite, migrations, `filament:assets`) and serves it at
<http://127.0.0.1:8000/admin>. `composer build` runs the build alone.

The panel is defined in `workbench/app/Providers/AdminPanelProvider.php` at a 4.5rem collapsed
rail and 17rem open width, with filler navigation chosen to exercise the parts of the sidebar
that are gated on `isOpen`: long and short labels, badges, a group without an icon (inline list,
gated header) and two groups with icons (core's dropdown flyout). Two extra pages are there to
test SPA navigation, which replaces the sidebar node and forces the JS to rebind.

The **Settings** page prints `$store.fhs.pinned`, `$store.fhs.peeking`, `$store.sidebar.isOpen`
and the `fhs-pinned` body class live. The **Reports** page holds a wide table and prints
`.fi-main-ctn`'s left edge, which must not move while the sidebar is open.

## Local development against a real app

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

## Testing

```bash
composer test
```

The package suite covers the PHP surface: plugin defaults, fluent configuration, the
`sidebarCollapsibleOnDesktop()` side effect, the render-hook payload and its scoping, panel
isolation, and asset registration.

Note that the render-hook tests deliberately render each hook the way *core* renders it —
`BODY_START` with a page class as its scope, `TOPBAR_START` with no scopes — rather than with
the scope the plugin registered under. Asserting against the plugin's own scope is
self-consistent and proves nothing.

The layout contract itself is browser-level, and a Pest v4 browser test in a host application is
the only meaningful coverage:

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

## Relationship to other packages

Deliberately not part of `vitisstudio/filament-custom-layout` — that package is a record-page
content contract (regions, presenters, descriptors); this one is panel chrome. No shared
abstraction, disjoint install surface, and the CSS here overrides core internals so it carries
the fragile half of the version risk.

## Upstream references

- `vendor/filament/filament/resources/js/stores/sidebar.js` — the `isOpen` store
- `vendor/filament/filament/resources/css/components/sidebar.css` — `lg:sticky`, widths
- `vendor/filament/filament/resources/views/components/sidebar/item.blade.php` — tooltip `x-effect`
- `vendor/filament/filament/resources/views/components/sidebar/group.blade.php` — group dropdown flyout
- `vendor/filament/filament/src/Panel/Concerns/HasSidebar.php` — width + collapsible API
- `vendor/filament/filament/src/View/PanelsRenderHook.php` — hook constants

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for what has changed recently.

## Credits

- [Vitis Studio](https://github.com/vitisstudio)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
