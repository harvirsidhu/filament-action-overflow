<?php

declare(strict_types=1);

namespace Harvirsidhu\FilamentActionOverflow;

use Filament\Actions\ActionGroup;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentActionOverflowServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-action-overflow';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command
                    ->publishConfigFile()
                    ->askToStarRepoOnGitHub('harvirsidhu/filament-action-overflow');
            });

        if (file_exists($package->basePath('/../config/action-overflow.php'))) {
            $package->hasConfigFile('action-overflow');
        }
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(ActionOverflowManager::class, fn (): ActionOverflowManager => new ActionOverflowManager);
    }

    public function packageBooted(): void
    {
        $this->registerActionGroupMacro();
        $this->registerAssets();
    }

    /**
     * The split button's stylesheet (`ActionOverflow::split()`), published
     * with the app's other Filament assets by `php artisan filament:assets`
     * (which `filament:upgrade` runs).
     */
    protected function registerAssets(): void
    {
        if (! class_exists(FilamentAsset::class)) {
            return;
        }

        FilamentAsset::register([
            Css::make('filament-action-overflow', __DIR__ . '/../resources/dist/filament-action-overflow.css'),
        ], package: 'harvirsidhu/filament-action-overflow');
    }

    protected function registerActionGroupMacro(): void
    {
        if (! class_exists(ActionGroup::class)) {
            return;
        }

        if (! ActionGroup::hasMacro('withOverflow')) {
            $macro = require __DIR__ . '/Macros/with_overflow_macro.php';
            ActionGroup::macro('withOverflow', $macro);
        }

        if (! ActionGroup::hasMacro('withSplit')) {
            $splitMacro = require __DIR__ . '/Macros/with_split_macro.php';
            ActionGroup::macro('withSplit', $splitMacro);
        }
    }
}
