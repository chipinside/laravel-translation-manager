<?php

namespace Barryvdh\TranslationManager\Console;

use Illuminate\Console\Command;
use Barryvdh\TranslationManager\Manager;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ExportCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'translations:export';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export translations to PHP files';

    /**
     * @var \Barryvdh\TranslationManager\Manager
     */
    protected $manager;

    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $groups = $this->argument('group');
        $json = $this->option('json');

        $all = (empty($groups) and !$json);


        if ($all) {
            $this->manager->exportAllTranslations();
        } else {
            $groups = array_filter(array_merge($groups,[
                $json ? Manager::JSON_GROUP : null
            ]));
            $this->manager->exportTranslations(...$groups);
        }

        if ($all) {
            $this->info('Done writing language files for ALL groups');
        } elseif ($json and (count($groups) === 1)) {
            $this->info('Done writing JSON language files for translation strings');
        } elseif (!empty($groups)) {
            if (in_array(Manager::JSON_GROUP, $groups)) {
                unset($groups[array_search(Manager::JSON_GROUP,$groups)]);
                $groups[] = 'the JSON language files for translation strings';
            }
            $this->info(sprintf('Done writing language files for group(s) %s',
                implode(', ', array_reverse(
                    array_merge(
                        [implode(' and ', array_splice($groups,-2))],
                        array_reverse($groups)
                    )
                ))
            ));
        }
    }

    /**
     * Get the console command arguments.
     */
    protected function getArguments(): array
    {
        return [
            ['group', InputArgument::IS_ARRAY + InputArgument::OPTIONAL, 'The groups to export (omit for all).'],
        ];
    }

    /**
     * Get the console command options.
     */
    protected function getOptions(): array
    {
        return [
            ['json', 'J', InputOption::VALUE_NONE, 'Export anonymous strings to JSON'],
            ['all', 'A', InputOption::VALUE_NONE, 'Export all groups'],
        ];
    }
}
