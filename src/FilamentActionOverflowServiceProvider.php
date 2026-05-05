<?php

declare(strict_types=1);

namespace Harvirsidhu\FilamentActionOverflow;

use Filament\Actions\ActionGroup;
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
    }

    protected function registerActionGroupMacro(): void
    {
        if (! class_exists(ActionGroup::class)) {
            return;
        }

        if (ActionGroup::hasMacro('withOverflow')) {
            return;
        }

        $macro = require __DIR__ . '/Macros/with_overflow_macro.php';
        ActionGroup::macro('withOverflow', $macro);
    }
}
