<?php

namespace Barryvdh\TranslationManager;

use Illuminate\Support\ServiceProvider;
use Barryvdh\TranslationManager\Manager;

class ManagerServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     */
    protected $defer = false;

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->configure();

        $this->app->singleton('translation-manager', function ($app) {
            return $app->make(Manager::class);
        });

        $this->registerCommands();
    }

    /**
     * Bootstrap the application events.
     */
    public function boot(): void
    {
        $this->registerResources();
        $this->offerPublishing();
        $this->registerRoutes();
    }

    protected function registerResources()
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'translation-manager');
    }

    protected function registerRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__.'/routes.php');
    }

    protected function offerPublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../stubs/TranslationsServiceProvider.stub' => app_path('Providers/TranslationsServiceProvider.php')
            ], 'translations-provider');

            $this->publishes([
                __DIR__.'/../config/translation-manager.php' => config_path('translation-manager.php')
            ], 'translations-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/translation-manager'),
            ], 'translations-views');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'translations-migrations');
        }
    }

    protected function registerCommands()
    {
        $this->app->singleton('command.translation-manager.reset', function ($app) {
            return new Console\ResetCommand($app['translation-manager']);
        });
        $this->commands('command.translation-manager.reset');

        $this->app->singleton('command.translation-manager.import', function ($app) {
            return new Console\ImportCommand($app['translation-manager']);
        });
        $this->commands('command.translation-manager.import');

        $this->app->singleton('command.translation-manager.find', function ($app) {
            return new Console\FindCommand($app['translation-manager']);
        });
        $this->commands('command.translation-manager.find');

        $this->app->singleton('command.translation-manager.export', function ($app) {
            return new Console\ExportCommand($app['translation-manager']);
        });
        $this->commands('command.translation-manager.export');

        $this->app->singleton('command.translation-manager.clean', function ($app) {
            return new Console\CleanCommand($app['translation-manager']);
        });
        $this->commands('command.translation-manager.clean');
    }

    protected function configure()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/translation-manager.php', 'translation-manager');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [
            'translation-manager',
            'command.translation-manager.reset',
            'command.translation-manager.import',
            'command.translation-manager.find',
            'command.translation-manager.export',
            'command.translation-manager.clean',
        ];
    }
}
