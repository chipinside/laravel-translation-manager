<?php

namespace Barryvdh\TranslationManager\Console;

use Illuminate\Console\Command;
use Barryvdh\TranslationManager\Manager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ImportCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'translations:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import translations from the PHP sources';

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
        $replace = $this->option('replace');
        $sync = $this->option('sync');
        $groups = $this->argument('group');
        $counter = 0;
        if (empty($groups)) {
            $counter = $this->manager->importTranslations($replace, $sync);
        } else foreach($groups as $group) {
            $counter += $this->manager->importTranslations($replace, $sync, null, $group);
        }
        $this->info('Done importing, processed '.$counter.' items!');
    }

    /**
     * Get the console command options.
     */
    protected function getOptions(): array
    {
        return [
            ['replace', 'R', InputOption::VALUE_NONE, 'Replace existing keys'],
            ['sync', 'S', InputOption::VALUE_NONE, 'Remove non-existing keys'],
        ];
    }

    /**
     * Get the console command arguments.
     */
    protected function getArguments(): array
    {
        return [
            ['group', InputArgument::IS_ARRAY + InputArgument::OPTIONAL]
        ];
    }
}
