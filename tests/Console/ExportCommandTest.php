<?php

namespace Barryvdh\TranslationManager\Tests\Console;

use Barryvdh\TranslationManager\Manager;
use Barryvdh\TranslationManager\Tests\TestCase;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Mockery\MockInterface;

/**
 * @covers \Barryvdh\TranslationManager\Console\ExportCommand
 */
class ExportCommandTest extends TestCase
{
    protected Manager|MockInterface|null $manager;
    protected Filesystem|MockInterface|null $filesystem;
    protected Dispatcher|MockInterface|null $dispatcher;

    public function setUp(): void
    {
        parent::setUp();
        ($this->filesystem = $this->mock(Filesystem::class))
            ->shouldReceive('exists')->andReturn(false);
        ($this->dispatcher = $this->mock(Dispatcher::class));
        $this->manager = \Mockery::mock(Manager::class, [app(),$this->filesystem,$this->dispatcher]);
        $this->instance(Manager::class, $this->manager);
    }

    public function testExportCommand(): void
    {
        $this->manager->shouldReceive('exportAllTranslations')
            ->withNoArgs()->once();

        $this->artisan('translations:export')
            ->expectsOutput('Done writing language files for ALL groups')
            ->assertSuccessful();

    }

    public function testExportCommandWithOneGroup(): void
    {
        $this->manager->shouldReceive('exportTranslations')
            ->withArgs(['group0'])->once();

        $this->artisan('translations:export group0')
            ->expectsOutput('Done writing language files for group(s) group0')
            ->assertSuccessful();
    }

    public function testExportCommandWithTwoGroups(): void
    {
        $this->manager->shouldReceive('exportTranslations')
            ->withArgs(['group0','group1'])->once();

        $this->artisan('translations:export group0 group1')
            ->expectsOutput('Done writing language files for group(s) group0 and group1')
            ->assertSuccessful();
    }

    public function testExportCommandWithThreeGroups(): void
    {
        $this->manager->shouldReceive('exportTranslations')
            ->withArgs(['group0','group1','group2'])->once();

        $this->artisan('translations:export group0 group1 group2')
            ->expectsOutput('Done writing language files for group(s) group0, group1 and group2')
            ->assertSuccessful();
    }

    public function testExportCommandWithOneGroupAndJSON(): void
    {
        $this->manager->shouldReceive('exportTranslations')
            ->withArgs(['group0','_json'])->once();

        $this->artisan('translations:export --json group0')
            ->expectsOutput('Done writing language files for group(s) group0 and ' .
                'the JSON language files for translation strings')
            ->assertSuccessful();
    }

    public function testExportCommandWithTwoGroupsAndJSON(): void
    {
        $this->manager->shouldReceive('exportTranslations')
            ->withArgs(['group0','group1','_json'])->once();

        $this->artisan('translations:export --json group0 group1')
            ->expectsOutput('Done writing language files for group(s) group0, group1 and ' .
                'the JSON language files for translation strings')
            ->assertSuccessful();
    }

    public function testExportCommandWithThreeGroupsAndJSON(): void
    {
        $this->manager->shouldReceive('exportTranslations')
            ->withArgs(['group0','group1','group2','_json'])->once();

        $this->artisan('translations:export --json group0 group1 group2')
            ->expectsOutput('Done writing language files for group(s) group0, group1, group2 and ' .
                'the JSON language files for translation strings')
            ->assertSuccessful();
    }

    public function testExportCommandWithJSONOnly(): void
    {
        $this->manager->shouldReceive('exportTranslations')->withArgs(['_json'])->once();

        $this->artisan('translations:export --json')
            ->expectsOutput('Done writing JSON language files for translation strings')
            ->assertSuccessful();
    }
}
