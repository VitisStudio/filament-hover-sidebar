<?php

namespace VitisStudio\FilamentHoverSidebar;

use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentHoverSidebarServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-hover-sidebar';

    public static string $viewNamespace = 'filament-hover-sidebar';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasTranslations()
            ->hasViews(static::$viewNamespace)
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command->askToStarRepoOnGitHub('vitisstudio/filament-hover-sidebar');
            });
    }

    public function packageBooted(): void
    {
        // Registered panel-agnostically, so both files load everywhere. Nothing activates until
        // a panel adds the plugin: the JS no-ops without `window.fhsConfig`, and the CSS is
        // gated on the `fhs` body class the plugin adds.
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );
    }

    protected function getAssetPackageName(): ?string
    {
        return 'vitisstudio/filament-hover-sidebar';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        return [
            Css::make('filament-hover-sidebar', __DIR__ . '/../resources/dist/hover-sidebar.css'),
            Js::make('filament-hover-sidebar', __DIR__ . '/../resources/dist/hover-sidebar.js'),
        ];
    }
}
