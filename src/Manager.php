<?php

namespace Barryvdh\TranslationManager;

use Barryvdh\TranslationManager\Events\TranslationsAfterImportEvent;
use Barryvdh\TranslationManager\Events\TranslationsAfterPublish;
use Barryvdh\TranslationManager\Events\TranslationsBeforeExportEvent;
use Barryvdh\TranslationManager\Events\TranslationsBeforeImportEvent;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Collection;
use Lang;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Barryvdh\TranslationManager\Models\Translation;
use Barryvdh\TranslationManager\Events\TranslationsAfterExportEvent;
use Symfony\Component\Finder\SplFileInfo;
use const PHP_EOL;

class Manager
{
    public const JSON_GROUP = '_json';

    protected Application $app;

    protected Filesystem $files;

    protected Dispatcher $events;

    protected array $config;

    protected array $locales;

    protected array $ignoreLocales;

    protected string $ignoreFilePath;

    public static string $translationModel = Translation::class;

    /**
     * @throws FileNotFoundException
     */
    public function __construct(Application $app, Filesystem $files, Dispatcher $events)
    {
        $this->app = $app;
        $this->files = $files;
        $this->events = $events;
        $this->config = $app['config']['translation-manager'];
        $this->ignoreFilePath = storage_path('.ignore_locales');
        $this->locales = [];
        $this->ignoreLocales = $this->getIgnoredLocales();
    }

    /**
     * Get the translation model class name.
     *
     * @return string
     */
    public static function translationModel()
    {
        return static::$translationModel;
    }

    /**
     * Get a new translation model instance.
     *
     * @return Translation
     */
    public static function translation()
    {
        return new static::$translationModel;
    }

    /**
     * @throws FileNotFoundException
     */
    protected function getIgnoredLocales()
    {
        if (!$this->files->exists($this->ignoreFilePath)) {
            return [];
        }
        $result = json_decode($this->files->get($this->ignoreFilePath), false, 512);

        return ($result && is_array($result)) ? $result : [];
    }

    public function importTranslations(bool $replace = false, bool $sync = false, string|null $base = null, string|false $import_group = false): int
    {
        $this->events->dispatch(new TranslationsBeforeImportEvent($replace, $base, $import_group));

        $imports = collect($this->importTranslationBase($base, $import_group));

        if ($sync) {
            static::translation()->query()
                ->whereIn('group', $imports->groupBy('group')->keys()->all())
                ->delete();
        }
        $imports->chunk(1000)->each(function (Collection $translations) use ($replace) {
            if ($replace) {
                static::translation()->query()->upsert($translations->all(), ['locale', 'group', 'key'], ['value']);
            } else {
                static::translation()->query()->insertOrIgnore($translations->all());
            }
        });

        $this->events->dispatch(new TranslationsAfterImportEvent($replace, $base, $import_group, $imports->count()));

        return $imports->count();
    }

    protected function importTranslationBase(?string $base, string|false $import_group): array
    {
        //allows for vendor lang files to be properly recorded through recursion.
        $vendor = true;
        if (null === $base) {
            $base = $this->app['path.lang'];
            $vendor = false;
        }

        $imports = [];

        foreach ($this->files->directories($base) as $langPath) {
            $locale = basename($langPath);

            //import langfiles for each vendor
            if ('vendor' === $locale) {
                foreach ($this->files->directories($langPath) as $vendor) {
                    $imports = array_merge(
                        $this->importTranslationBase($vendor, $import_group),
                        $imports,
                    );
                }
                continue;
            }

            if (in_array($locale, $this->config['exclude_langs'], true)) {
                continue;
            }

            $vendorName = $this->files->name($this->files->dirname($langPath));
            foreach ($this->files->allfiles($langPath) as $file) {
                $info = pathinfo($file);
                $group = $info['filename'];
                $subLangPath = str_replace($langPath . DIRECTORY_SEPARATOR, '', $info['dirname']);
                $subLangPath = str_replace(DIRECTORY_SEPARATOR, '/', $subLangPath);
                $langPath = str_replace(DIRECTORY_SEPARATOR, '/', $langPath);

                if ($subLangPath !== $langPath) {
                    $group = $subLangPath . '/' . $group;
                }

                if ($vendor) {
                    $group = 'vendor/' . $vendorName;
                }

                if ($import_group && $import_group !== $group) {
                    continue;
                }

                if (in_array($group, $this->config['exclude_groups'], true)) {
                    continue;
                }

                $translations = ($vendor)
                    ? $translations = include $file
                    : Lang::getLoader()->load($locale, $group);

                if ($translations && is_array($translations)) {
                    foreach (Arr::dot($translations) as $key => $value) {
                        $translation = $this->importTranslation($key, $value, $locale, $group);
                        if (!empty($translation)) {
                            $imports[] = $translation;
                        }
                    }
                }
            }
        }

        if (!$import_group || in_array($import_group, ['json', '_json'])) {
            foreach ($this->files->files($this->app['path.lang']) as $jsonTranslationFile) {
                if (!str_contains($jsonTranslationFile, '.json')) {
                    continue;
                }
                $locale = basename($jsonTranslationFile, '.json');

                if (in_array($locale, $this->config['exclude_langs'], true)) {
                    continue;
                }

                $group = self::JSON_GROUP;
                $translations =
                    Lang::getLoader()->load($locale, '*', '*'); // Retrieves JSON entries of the given locale only
                if ($translations && is_array($translations)) {
                    foreach ($translations as $key => $value) {
                        $translation = $this->importTranslation($key, $value, $locale, $group);
                        if (!empty($translation)) {
                            $imports[] = $translation;
                        }
                    }
                }
            }
        }

        return $imports;
    }

    protected function importTranslation($key, $value, $locale, $group): ?array
    {
        // process only string values
        if (is_array($value)) {
            return null;
        }
        $value = (string) $value;

        return [
            'locale' => $locale,
            'group' => $group,
            'key' => $key,
            'value' => $value
        ];
    }

    public function findTranslations($path = null): int
    {
        $path = $path ?: base_path();
        $groupKeys = [];
        $stringKeys = [];
        $functions = $this->config['trans_functions'];

        $groupPattern =                          // See https://regex101.com/r/WEJqdL/6
            "[^\w|>]" .                          // Must not have an alphanum or _ or > before real method
            '(' . implode('|', $functions) . ')' .  // Must start with one of the functions
            "\(" .                               // Match opening parenthesis
            "[\'\"]" .                           // Match " or '
            '(' .                                // Start a new group to match:
            '[\/a-zA-Z0-9_-]+' .                 // Must start with group
            "([.](?! )[^\1)]+)+" .               // Be followed by one or more items/keys
            ')' .                                // Close group
            "[\'\"]" .                           // Closing quote
            "[\),]";                             // Close parentheses or new parameter

        $stringPattern =
            "[^\w]".                                     // Must not have an alphanum before real method
            '('.implode('|', $functions).')'.             // Must start with one of the functions
            "\(\s*".                                       // Match opening parenthesis
            "(?P<quote>['\"])".                            // Match " or ' and store in {quote}
            "(?P<string>(?:\\\k{quote}|(?!\k{quote}).)*)". // Match any string that can be {quote} escaped
            "\k{quote}".                                   // Match " or ' previously matched
            "\s*[\),]";                                    // Close parentheses or new parameter

        // Find all PHP + Twig files in the app folder, except for storage
        $finder = new Finder();
        $finder->in($path)->exclude('storage')->exclude('vendor')->name('*.php')->name('*.twig')->name('*.vue')->files();

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            // Search the current file for the pattern
            if (preg_match_all("/$groupPattern/siU", $file->getContents(), $matches)) {
                // Get all matches
                foreach ($matches[2] as $key) {
                    $groupKeys[] = $key;
                }
            }

            if (preg_match_all("/$stringPattern/siU", $file->getContents(), $matches)) {
                foreach ($matches['string'] as $key) {
                    if (preg_match("/(^[\/a-zA-Z0-9_-]+([.][^\1)\ ]+)+$)/siU", $key, $groupMatches)) {
                        // group{.group}.key format, already in $groupKeys but also matched here
                        // do nothing, it has to be treated as a group
                        continue;
                    }

                    //TODO: This can probably be done in the regex, but I couldn't do it.
                    //skip keys which contain namespacing characters, unless they also contain a
                    //space, which makes it JSON.
                    if (Str::contains($key, ' ') || !(Str::contains($key, '::') && Str::contains($key, '.'))) {
                        $stringKeys[] = $key;
                    }
                }
            }
        }
        // Remove duplicates
        $groupKeys = array_unique($groupKeys);
        $stringKeys = array_unique($stringKeys);

        // Add the translations to the database, if not existing.
        foreach ($groupKeys as $key) {
            // Split the group and item
            [$group, $item] = explode('.', $key, 2);
            $this->missingKey('', $group, $item);
        }

        foreach ($stringKeys as $key) {
            $group = self::JSON_GROUP;
            $item = $key;
            $this->missingKey('', $group, $item);
        }

        // Return the number of found translations
        return count($groupKeys + $stringKeys);
    }

    public function missingKey($namespace, $group, $key): void
    {
        if (!in_array($group, $this->config['exclude_groups'], true)) {
            $translation = static::translation()->query()->where([
                'locale' => $this->app['config']['app.locale'],
                'group' => $group,
                'key' => $key,
            ])->first();

            if (is_null($translation)) {
                $translation = static::translation();
                $translation->locale = $this->app['config']['app.locale'];
                $translation->group = $group;
                $translation->key = $key;
                $translation->save();
            }
        }
    }

    protected function export($expression): string {
        $export = var_export($expression, TRUE);
        $patterns = [
            "/array \(/" => '[',
            "/^([ ]*)\)(,?)$/m" => '$1]$2',
            "/=>[ ]?\n[ ]+\[/" => '=> [',
            "/([ ]*)(\'[^\']+\') => ([\[\'])/" => '$1$2 => $3',
            "/\\\\{2}/" => '\\'
        ];
        return preg_replace(array_keys($patterns), array_values($patterns), $export);
    }

    public function exportTranslations(...$groups): void
    {
        $this->events->dispatch(new TranslationsBeforeExportEvent($groups));

        foreach ($groups as $group) {
            $this->exportTranslationsGroup($group);
        }

        $this->events->dispatch(new TranslationsAfterExportEvent($groups));
    }

    public function exportTranslationsGroup($group = null): void
    {
        $group = basename($group);
        $basePath = $this->app['path.lang'];

        if ($group !== self::JSON_GROUP) {
            if (!in_array($group, $this->config['exclude_groups'], true)) {
                $vendor = false;

                if (Str::startsWith($group, 'vendor')) {
                    $vendor = true;
                }

                $tree = $this->makeTree(static::translation()->query()->ofTranslatedGroup($group)
                    ->orderByGroupKeys(Arr::get($this->config, 'sort_keys', false))
                    ->get());

                foreach ($tree as $locale => $groups) {
                    $locale = basename($locale);
                    if (isset($groups[$group])) {
                        $translations = $groups[$group];
                        $path = $this->app['path.lang'];

                        $locale_path = $locale.DIRECTORY_SEPARATOR.$group;
                        if ($vendor) {
                            $path = $basePath.'/'.$group.'/'.$locale;
                            $locale_path = Str::after($group, '/');
                        }
                        $subFolders = explode(DIRECTORY_SEPARATOR, $locale_path);
                        array_pop($subFolders);

                        $subFolder_level = '';
                        foreach ($subFolders as $subFolder) {
                            $subFolder_level .= $subFolder.DIRECTORY_SEPARATOR;

                            $temp_path = rtrim($path.DIRECTORY_SEPARATOR.$subFolder_level, DIRECTORY_SEPARATOR);
                            if (!is_dir($temp_path)) {
                                mkdir($temp_path, 0777, true);
                            }
                        }

                        $path .= DIRECTORY_SEPARATOR.$locale.DIRECTORY_SEPARATOR.$group.'.php';

                        $output = "<?php\n\nreturn ".$this->export($translations).';'. PHP_EOL;
                        $this->files->put($path, $output);
                    }
                }
            }
        } else {
            $tree = $this->makeTree(static::translation()->query()->ofTranslatedGroup(self::JSON_GROUP)
                ->orderByGroupKeys(Arr::get($this->config, 'sort_keys', false))
                ->get(), true);

            foreach ($tree as $locale => $groups) {
                if (isset($groups[self::JSON_GROUP])) {
                    $translations = $groups[self::JSON_GROUP];
                    $path = $this->app['path.lang'].'/'.$locale.'.json';
                    $output = json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $this->files->put($path, $output);
                }
            }
        }
    }

    public function publish(?string $group = null): void
    {
        if (is_null($group)) {
            $this->exportAllTranslations();
        } else {
            $this->exportTranslations($group);
        }
        $this->events->dispatch(new TranslationsAfterPublish($group));
    }

    public function exportAllTranslations(): void
    {
        $groups = static::translation()->query()
            ->distinct('group')
            ->whereNotNull('value')
            ->pluck('group')
            ->toArray();

        $this->exportTranslations(...$groups);
    }

    protected function makeTree($translations, $json = false): array
    {
        $array = [];
        foreach ($translations as $translation) {
            if ($json) {
                $this->jsonSet(
                    $array[$translation->locale][$translation->group],
                    $translation->key,
                    $translation->value
                );
            } else {
                Arr::set(
                    $array[$translation->locale][$translation->group],
                    $translation->key,
                    $translation->value
                );
            }
        }

        return $array;
    }

    public function jsonSet(&$array, $key, $value)
    {
        if (is_null($key)) {
            return $array = $value;
        }
        $array[$key] = $value;

        return $array;
    }

    public function cleanTranslations(): void
    {
        static::translation()->query()->whereNull('value')->delete();
    }

    public function truncateTranslations(): void
    {
        static::translation()->query()->truncate();
    }

    public function getLocales(): array
    {
        if (empty($this->locales)) {
            $locales = array_merge(
                [config('app.locale')],
                static::translation()->query()->groupBy('locale')->pluck('locale')->toArray()
            );
            foreach ($this->files->directories($this->app->langPath()) as $localeDir) {
                if (($name = $this->files->name($localeDir)) !== 'vendor') {
                    $locales[] = $name;
                }
            }

            $this->locales = array_unique($locales);
            sort($this->locales);
        }

        return array_diff($this->locales, $this->ignoreLocales);
    }

    /**
     * @throws FileNotFoundException
     */
    public function addLocale($locale): bool
    {
        $localeDir = $this->app->langPath().'/'.basename($locale);

        $this->ignoreLocales = array_diff($this->ignoreLocales, [$locale]);
        $this->saveIgnoredLocales();
        $this->ignoreLocales = $this->getIgnoredLocales();

        if (!$this->files->exists($localeDir) || !$this->files->isDirectory($localeDir)) {
            return $this->files->makeDirectory($localeDir);
        }

        return true;
    }

    /**
     * @return bool|int
     */
    protected function saveIgnoredLocales()
    {
        return $this->files->put($this->ignoreFilePath, json_encode($this->ignoreLocales));
    }

    /**
     * @throws FileNotFoundException
     *
     * @return false|int
     */
    public function removeLocale($locale)
    {
        if (!$locale) {
            return false;
        }
        $this->ignoreLocales = array_merge($this->ignoreLocales, [$locale]);
        $this->saveIgnoredLocales();
        $this->ignoreLocales = $this->getIgnoredLocales();

        return static::translation()->query()->where('locale', $locale)->delete();
    }

    public function getConfig($key = null)
    {
        if (null === $key) {
            return $this->config;
        }

        return $this->config[$key];
    }
}
