<?php

namespace Workbench\App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;

class Settings extends Page
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected string $view = 'workbench::filament.pages.settings';
}
