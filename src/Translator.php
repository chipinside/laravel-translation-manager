<?php

namespace Barryvdh\TranslationManager;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Translation\Translator as LaravelTranslator;

class Translator extends LaravelTranslator
{
    /**
     * @var \Illuminate\Events\Dispatcher
     */
    protected $events;
    /**
     * @var \Barryvdh\TranslationManager\Manager
     */
    private $manager;

    /** @var Closure<Request>|null  */
    protected static Closure|null $authUsing = null;

    /** @var Closure<Authenticatable>|null  */
    protected static Closure|null $checkImportPermissionUsing = null;

    /** @var Closure<Authenticatable>|null  */
    protected static Closure|null $checkExportPermissionUsing = null;

    /** @var Closure<Authenticatable>|null  */
    protected static Closure|null $checkFindPermissionUsing = null;

    /** @var Closure<Authenticatable>|null  */
    protected static Closure|null $checkCreateGroupPermissionUsing = null;

    /** @var Closure<Authenticatable>|null  */
    protected static Closure|null $checkManageLocalesPermissionUsing = null;

    /** @var Closure<Authenticatable>|null  */
    protected static Closure|null $checkCreateKeyPermissionUsing = null;

    /**
     * Set the callback that should be used to authenticate Translation Manager users.
     *
     * @param Closure<Request> $callback
     * @return void
     */
    public static function auth(Closure $callback): void
    {
        static::$authUsing = $callback;
    }

    /**
     * @param Closure<Authenticatable> $callback
     * @return void
     */
    public static function authImportPermission(Closure $callback): void
    {
        static::$checkImportPermissionUsing = $callback;
    }

    /**
     * @param Closure<Authenticatable> $callback
     * @return void
     */
    public static function authExportPermission(Closure $callback): void
    {
        static::$checkExportPermissionUsing = $callback;
    }

    /**
     * @param Closure<Authenticatable> $callback
     * @return void
     */
    public static function authFindPermission(Closure $callback): void
    {
        static::$checkFindPermissionUsing = $callback;
    }

    /**
     * @param Closure<Authenticatable> $callback
     * @return void
     */
    public static function authCreateGroupPermission(Closure $callback): void
    {
        static::$checkCreateGroupPermissionUsing = $callback;
    }

    /**
     * @param Closure<Authenticatable> $callback
     * @return void
     */
    public static function authManageLocalesPermission(Closure $callback): void
    {
        static::$checkManageLocalesPermissionUsing = $callback;
    }

    /**
     * @param Closure<Authenticatable> $callback
     * @return void
     */
    public static function authCreateKeyPermission(Closure $callback): void
    {
        static::$checkCreateKeyPermissionUsing = $callback;
    }

    /**
     * Determine if the given request can access the Translation Manager.
     *
     * @param Request $request
     * @return mixed
     */
    public static function check(Request $request)
    {
        return (static::$authUsing ?: fn () => app()->environment('local'))($request);
    }

    /**
     * @param Authenticatable $user
     * @return bool
     */
    public static function checkImportPermission(Authenticatable $user): bool
    {
        return (static::$checkImportPermissionUsing ?: fn () => false)($user);
    }

    /**
     * @param Authenticatable $user
     * @return bool
     */
    public static function checkExportPermission(Authenticatable $user): bool
    {
        return (static::$checkExportPermissionUsing ?: fn () => false)($user);
    }

    /**
     * @param Authenticatable $user
     * @return bool
     */
    public static function checkFindPermission(Authenticatable $user): bool
    {
        return (static::$checkFindPermissionUsing ?: fn () => false)($user);
    }

    /**
     * @param Authenticatable $user
     * @return bool
     */
    public static function checkCreateGroupPermission(Authenticatable $user): bool
    {
        return (static::$checkCreateGroupPermissionUsing ?: fn () => false)($user);
    }

    /**
     * @param Authenticatable $user
     * @return bool
     */
    public static function checkManageLocalesPermission(Authenticatable $user): bool
    {
        return (static::$checkManageLocalesPermissionUsing ?: fn () => false)($user);
    }

    /**
     * @param Authenticatable $user
     * @return bool
     */
    public static function checkCreateKeyPermission(Authenticatable $user): bool
    {
        return (static::$checkCreateKeyPermissionUsing ?: fn () => false)($user);
    }


    /**
     * Get the translation for the given key.
     *
     * @param string $key
     * @param string $locale
     */
    public function get($key, array $replace = [], $locale = null, $fallback = true): string
    {
        // Get without fallback
        $result = parent::get($key, $replace, $locale, false);
        if ($result === $key) {
            $this->notifyMissingKey($key);

            // Reget with fallback
            $result = parent::get($key, $replace, $locale, $fallback);
        }

        return $result;
    }

    public function setTranslationManager(Manager $manager): void
    {
        $this->manager = $manager;
    }

    protected function notifyMissingKey($key): void
    {
        [$namespace, $group, $item] = $this->parseKey($key);
        if ($this->manager && '*' === $namespace && $group && $item) {
            $this->manager->missingKey($namespace, $group, $item);
        }
    }
}
