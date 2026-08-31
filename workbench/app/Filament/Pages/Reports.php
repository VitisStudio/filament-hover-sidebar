<?php

namespace Workbench\App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;

class Reports extends Page
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar';

    protected string $view = 'workbench::filament.pages.reports';
}
