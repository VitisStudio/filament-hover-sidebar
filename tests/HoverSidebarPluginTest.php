<?php

use Filament\Facades\Filament;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use VitisStudio\FilamentHoverSidebar\HoverSidebarPlugin;

function panel(): Panel
{
    return Panel::make()->id('test');
}

/**
 * The plugin only renders on the active panel, so a booted panel has to be the current one.
 */
function activePanel(): Panel
{
    $panel = panel();

    Filament::registerPanel($panel);
    Filament::setCurrentPanel($panel);

    return $panel;
}

/**
 * `@js` renders the config as `JSON.parse('...')` with quotes escaped for the JS string
 * literal, so unescape that layer before decoding the JSON itself.
 *
 * @return array<string, mixed>
 */
function fhsConfigFrom(string $html): array
{
    expect($html)->toMatch("/JSON\\.parse\\('(.*)'\\)/");

    preg_match("/JSON\\.parse\\('(.*)'\\)/", $html, $matches);

    return json_decode(json_decode('"' . $matches[1] . '"'), associative: true);
}

it('has sensible defaults', function () {
    $plugin = HoverSidebarPlugin::make();

    expect($plugin->getId())->toBe('filament-hover-sidebar')
        ->and($plugin->getOpenDelay())->toBe(90)
        ->and($plugin->getCloseDelay())->toBe(180)
        ->and($plugin->isPinnable())->toBeTrue()
        ->and($plugin->isPinnedByDefault())->toBeFalse();
});

it('is configurable fluently', function () {
    $plugin = HoverSidebarPlugin::make()
        ->openDelay(120)
        ->closeDelay(240)
        ->pinnable(false)
        ->pinnedByDefault();

    expect($plugin->getOpenDelay())->toBe(120)
        ->and($plugin->getCloseDelay())->toBe(240)
        ->and($plugin->isPinnable())->toBeFalse()
        ->and($plugin->isPinnedByDefault())->toBeTrue();
});

it('makes the panel sidebar collapsible on desktop', function () {
    $panel = panel();

    expect($panel->isSidebarCollapsibleOnDesktop())->toBeFalse();

    HoverSidebarPlugin::make()->register($panel);

    expect($panel->isSidebarCollapsibleOnDesktop())->toBeTrue();
});

it('boots the config and the body class before the body renders', function () {
    $panel = activePanel();

    HoverSidebarPlugin::make()->openDelay(120)->boot($panel);

    // Core passes the page's render-hook scopes here, never the panel id.
    $html = FilamentView::renderHook(PanelsRenderHook::BODY_START, scopes: [Dashboard::class])->toHtml();

    expect($html)
        ->toContain('document.body.classList.add("fhs")')
        ->toContain('window.fhsConfig');

    expect(fhsConfigFrom($html))->toBe([
        'openDelay' => 120,
        'closeDelay' => 180,
        'pinnable' => true,
        'pinnedByDefault' => false,
    ]);
});

it('renders the pin button in the topbar when pinnable', function () {
    $panel = activePanel();

    HoverSidebarPlugin::make()->boot($panel);

    // Core passes no scopes at all here.
    $html = FilamentView::renderHook(PanelsRenderHook::TOPBAR_START)->toHtml();

    expect($html)
        ->toContain('fhs-pin-btn-ctn')
        ->toContain('$store.fhs.togglePin()')
        ->toContain('Pin sidebar open')
        ->toContain('Unpin sidebar');
});

it('omits the pin button when not pinnable', function () {
    $panel = activePanel();

    HoverSidebarPlugin::make()->pinnable(false)->boot($panel);

    $html = FilamentView::renderHook(PanelsRenderHook::TOPBAR_START)->toHtml();

    expect($html)->not->toContain('fhs-pin-btn-ctn');
});

it('renders nothing on a panel that does not have the plugin', function () {
    $withPlugin = panel();

    Filament::registerPanel($withPlugin);
    HoverSidebarPlugin::make()->boot($withPlugin);

    // A second panel becomes current; the hooks are registered but must render nothing.
    $other = Panel::make()->id('other');

    Filament::registerPanel($other);
    Filament::setCurrentPanel($other);

    expect(FilamentView::renderHook(PanelsRenderHook::BODY_START, scopes: [Dashboard::class])->toHtml())->toBe('')
        ->and(FilamentView::renderHook(PanelsRenderHook::TOPBAR_START)->toHtml())->toBe('');
});
