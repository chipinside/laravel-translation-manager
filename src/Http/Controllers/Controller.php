<?php

namespace Barryvdh\TranslationManager\Http\Controllers;

use Barryvdh\TranslationManager\Manager;
use Barryvdh\TranslationManager\Translator;
use DB;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class Controller extends BaseController
{
    /** @var Manager */
    protected $manager;

    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param null $group
     *
     * @return Application|Factory|View
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getView(Request $request, $group = null)
    {
        return $this->getIndex($request, $group);
    }

    /**
     * @param null $group
     *
     * @return Application|Factory|View
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getIndex(Request $request, $group = null)
    {
        $groups = Manager::translation()->query()->groupBy('group');
        $excludedGroups = $this->manager->getConfig('exclude_groups');
        if ($excludedGroups) {
            $groups->whereNotIn('group', $excludedGroups);
        }

        $groups = $groups->select('group')->orderBy('group')->get()->pluck('group', 'group');
        if ($groups instanceof Collection) {
            $groups = $groups->all();
        }

        if (!is_null($group) and
            !in_array($group, $groups) and
            !Translator::checkCreateGroupPermission($request->user())
        ) {
            abort(403);
        }

        $groups = ['' => 'Choose a group'] + $groups;

        $custom_locales = $request->input('locale',false);
        $locales = collect($custom_locales
            ? $request->input('locale')
            : $this->manager->getLocales());

        $table = Manager::translation()->getTable();
        $query = DB::connection(Manager::translation()->getConnectionName())
            ->table($table)
            ->select([
                "$table.key",
                "$table.group"
            ])
            ->groupBy(["$table.group","$table.key"]);

        $orderRaw = [];
        $locales->each(function($locale) use ($query, $table, &$orderRaw) {
            $query->addSelect([
                "$locale.id as {$locale}_id",
                "$locale.value as {$locale}_value"
            ])->leftJoin("$table as $locale", function(JoinClause $join) use ($locale, $table){
                $join->on("$locale.key",'=',"$table.key")
                    ->where("$locale.locale",'=',$locale);
            })->groupBy("$locale.id");
            $orderRaw[] = "(trim(coalesce(\"$locale\".\"value\",'')) != '')";
        });
        $query->orderByRaw(join(' or ', $orderRaw));

        $order = $request->input('order');
        $desc = $request->input('desc',false) != false;
        if ($order) {
            if (strtolower($order) == 'key') {
                $query->orderBy(DB::raw("lower(\"{$table}\".\"key\") collate \"POSIX\""),$desc ? 'desc' : 'asc');
            } else {
                $query->orderBy(DB::raw("lower(\"{$order}\".\"value\") collate \"POSIX\""),$desc ? 'desc' : 'asc');
            }
        }

        $query->orderBy("$table.key");

        $search = $request->input('search', false);

        if ($search){
            $escaped_search = preg_replace(['/%/','/_/','/\?/'],['\%','\_','\?'], $search);
            $query->where(function(Builder $query) use ($table, $escaped_search, $locales) {
                $query->whereRaw("\"$table\".\"key\" ilike concat('%', ?::TEXT, '%')", $escaped_search);
                $locales->each(function($locale) use ($query, $escaped_search) {
                    $query->orWhereRaw("\"$locale\".\"value\" ilike concat('%', ?::TEXT, '%')", $escaped_search);
                });
            });
        }

        $query->where("$table.group",'=',$group);
        $numTranslations = $query->getCountForPagination();
        $paginationEnabled = $this->manager->getConfig('pagination_enabled');

        if ($paginationEnabled) {
            $page = request()->get('page', 1);
            $perPage = $this->manager->getConfig('per_page');
            $offSet = ($page * $perPage) - $perPage;
            $query->limit($perPage)->offset($offSet);
            $prefix = $this->manager->getConfig('route')['prefix'];
            $path = url("$prefix/view/$group");

            if ('bootstrap4' === $this->manager->getConfig('template')) {
                LengthAwarePaginator::useBootstrap();
            } elseif ('bootstrap5' === $this->manager->getConfig('template')) {
                LengthAwarePaginator::useBootstrap();
            }
        }

        $translations = $query->get()->mapWithKeys(function ($line) use ($locales) {
            $return = [];
            $locales->each(function($locale) use (&$return, $line) {
                $id = "{$locale}_id";
                $value = "{$locale}_value";
                $return[$locale] = (object) [
                    "id" => $line->$id,
                    "value" => $line->$value,
                ];
            });
            return [$line->key => $return];
        });

        if ($paginationEnabled) {
            $paginator = new LengthAwarePaginator($translations, $numTranslations, $perPage, $page);
            $translations = $paginator->withPath($path)->withQueryString();
        }

        return view('translation-manager::'.$this->manager->getConfig('template').'.index')
            ->with('translations', $translations)
            ->with('custom_locales', $custom_locales)
            ->with('locales', $locales)
            ->with('order', $order)
            ->with('desc', $desc)
            ->with('search', $search)
            ->with('groups', $groups)
            ->with('group', $group)
            ->with('numTranslations', $numTranslations)
            ->with('editUrl', $group ? action([Controller::class,'postEdit'], [$group]) : null)
            ->with('paginationEnabled', $this->manager->getConfig('pagination_enabled'))
            ->with('deleteEnabled', $this->manager->getConfig('delete_enabled'));
    }

    protected function loadLocales(): array
    {
        //Set the default locale as the first one.
        $locales = Manager::translation()->query()->groupBy('locale')
            ->select('locale')
            ->get()
            ->pluck('locale');

        if ($locales instanceof Collection) {
            $locales = $locales->all();
        }
        $locales = array_merge([config('app.locale')], $locales);

        return array_unique($locales);
    }

    public function postAdd(Request $request, $group = null): RedirectResponse
    {
        if (Translator::checkCreateGroupPermission($request->user())) {

            $keys = explode("\n", request()->get('keys'));

            foreach ($keys as $key) {
                $key = trim($key);
                if ($group && $key) {
                    $this->manager->missingKey('*', $group, $key);
                }
            }

            return redirect()->back();
        } else {
            abort(403);
        }

    }

    public function postEdit($group = null)
    {
        if (!in_array($group, $this->manager->getConfig('exclude_groups'), true)) {
            $name = request()->get('name');
            $value = request()->get('value');

            [$locale, $key] = explode('|', $name, 2);

            $translation = Manager::translation()->query()->where([
                'locale' => $locale,
                'group' => $group,
                'key' => $key,
            ])->first();

            if (is_null($translation)) {
                $translation = Manager::translation();
                $translation->locale = $locale;
                $translation->group = $group;
                $translation->key = $key;
            }

            $translation->value = (string) $value ?: null;

            $translation->save();

            return ['status' => 'ok'];
        }
    }

    public function postDelete($group, $key)
    {
        if ($this->manager->getConfig('delete_enabled') && !in_array($group, $this->manager->getConfig('exclude_groups'), true)) {
            Manager::translation()->query()
                ->where('group', $group)
                ->where('key', $key)
                ->delete();

            return ['status' => 'ok'];
        }
    }

    public function postImport(Request $request): array
    {
        if (Translator::checkImportPermission($request->user())) {
            $replace = $request->get('replace', false);
            $sync = $request->get('sync', false);
            $counter = $this->manager->importTranslations($replace, $sync);

            return ['status' => 'ok', 'counter' => $counter];
        } else {
            abort(403);
        }
    }

    public function postFind(Request $request): array
    {
        if (Translator::checkFindPermission($request->user())) {
            $numFound = $this->manager->findTranslations();

            return ['status' => 'ok', 'counter' => (int)$numFound];
        } else {
            abort(403);
        }
    }

    public function postPublish(Request $request, $group = null): array
    {
        if (Translator::checkExportPermission($request->user())) {
            $json = false;

            if ('_json' === $group) {
                $json = true;
            }

            $this->manager->exportTranslations($group, $json);

            return ['status' => 'ok'];
        } else {
            abort(403);
        }
    }

    public function postAddGroup(Request $request): RedirectResponse
    {
        if (Translator::checkCreateGroupPermission($request->user())) {
            $group = str_replace('.', '', $request->input('new-group'));
            if ($group) {
                return redirect()->action([Controller::class, 'getView'], $group);
            }

            return redirect()->back();
        } else {
            abort(403);
        }
    }

    /**
     * @throws FileNotFoundException
     */
    public function postAddLocale(Request $request): RedirectResponse
    {
        if (Translator::checkManageLocalesPermission($request->user())) {
            $locales = $this->manager->getLocales();
            $newLocale = str_replace([], '-', trim($request->input('new-locale')));
            if (!$newLocale || in_array($newLocale, $locales, true)) {
                return redirect()->back();
            }
            $this->manager->addLocale($newLocale);

            return redirect()->back();
        } else {
            abort(403);
        }
    }

    /**
     * @throws FileNotFoundException
     */
    public function postRemoveLocale(Request $request): RedirectResponse
    {
        if (Translator::checkManageLocalesPermission($request->user())) {
            foreach ($request->input('remove-locale', []) as $locale => $val) {
                $this->manager->removeLocale($locale);
            }

            return redirect()->back();
        } else {
            abort(403);
        }

    }

    public function postTranslateMissing(Request $request): RedirectResponse
    {
        $locales = $this->manager->getLocales();
        $newLocale = str_replace([], '-', trim($request->input('new-locale')));
        if ($request->has('with-translations') && $request->has('base-locale') && in_array($request->input('base-locale'), $locales) && $request->has('file') && in_array($newLocale, $locales)) {
            $base_locale = $request->get('base-locale');
            $group = $request->get('file');
            $base_strings = Manager::translation()->query()
                ->where('group', $group)
                ->where('locale', $base_locale)
                ->get();

            foreach ($base_strings as $base_string) {
                $base_query = Manager::translation()->query()
                    ->where('group', $group)
                    ->where('locale', $newLocale)
                    ->where('key', $base_string->key);

                if ($base_query->exists() && $base_query->whereNotNull('value')->exists()) {
                    // Translation already exists. Skip
                    continue;
                }
                $translated_text = Str::apiTranslateWithAttributes($base_string->value, $newLocale, $base_locale);
                request()->replace([
                    'value' => $translated_text,
                    'name' => $newLocale.'|'.$base_string->key,
                ]);
                app()->call([Controller::class,'postEdit'],['group' => $group]);
            }

            return redirect()->back();
        }

        return redirect()->back();
    }
}
