<?php

namespace NextMigrant\Plunk;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class PlunkServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-plunk')
            ->hasConfigFile();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(PlunkManager::class, function ($app) {
            return new PlunkManager(
                config: $app['config']->get('plunk', []),
            );
        });
    }
}
