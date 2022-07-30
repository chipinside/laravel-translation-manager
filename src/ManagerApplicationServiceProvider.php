<?php

namespace Barryvdh\TranslationManager;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class ManagerApplicationServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->authorization();
    }

    /**
     * Configure the Translation authorization services.
     *
     * @return void
     */
    protected function authorization()
    {
        $this->gate();

        Translator::auth(function ($request) {
            return Gate::check('viewTranslationManager', [$request->user()] || app()->environment('local'));
        });

        Translator::authImportPermission(function ($user) {
            return Gate::check('importTranslations', [$user]) || app()->environment('local');
        });
        Translator::authExportPermission(function ($user) {
            return Gate::check('exportTranslations', [$user]) || app()->environment('local');
        });
        Translator::authFindPermission(function ($user) {
            return Gate::check('findTranslations', [$user]) || app()->environment('local');
        });
        Translator::authCreateGroupPermission(function ($user) {
            return Gate::check('createTranslationsGroup', [$user]) || app()->environment('local');
        });
        Translator::authManageLocalesPermission(function ($user) {
            return Gate::check('manageTranslationsLocales', [$user]) || app()->environment('local');
        });
        Translator::authCreateKeyPermission(function ($user) {
            return Gate::check('createTranslationKey', [$user]) || app()->environment('local');
        });
    }

    protected function setConnection(string $connection) {
        config(['translation-manager.connection' => $connection]);
    }

    /**
     * Register the Translation gates.
     *
     * This gate determines who can access Translations features.
     *
     * @return void
     */
    protected function gate()
    {
        Gate::define('viewTranslationManager', function ($user): bool {
            return in_array($user->email, [
                //
            ]);
        });

        Gate::define('importTranslations', function ($user): bool {
            return in_array($user->email, [
                //
            ]);
        });

        Gate::define('exportTranslations', function ($user): bool {
            return in_array($user->email, [
                //
            ]);
        });

        Gate::define('findTranslations', function ($user): bool {
            return in_array($user->email, [
                //
            ]);
        });

        Gate::define('createTranslationsGroup', function ($user): bool {
            return in_array($user->email, [
                //
            ]);
        });

        Gate::define('manageTranslationsLocales', function ($user): bool {
            return in_array($user->email, [
                //
            ]);
        });

        Gate::define('createTranslationKey', function ($user): bool {
            return in_array($user->email, [
                //
            ]);
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
