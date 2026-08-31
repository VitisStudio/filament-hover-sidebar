<?php

use Filament\Support\Facades\FilamentAsset;

it('registers the stylesheet and script with Filament', function () {
    expect(FilamentAsset::getStyleHref('filament-hover-sidebar', package: 'vitisstudio/filament-hover-sidebar'))
        ->toContain('filament-hover-sidebar')
        ->and(FilamentAsset::getScriptSrc('filament-hover-sidebar', package: 'vitisstudio/filament-hover-sidebar'))
        ->toContain('filament-hover-sidebar');
});

it('ships both assets as plain, unbuilt files', function () {
    expect(__DIR__ . '/../resources/dist/hover-sidebar.css')->toBeReadableFile()
        ->and(__DIR__ . '/../resources/dist/hover-sidebar.js')->toBeReadableFile();
});
