<?php

namespace Barryvdh\TranslationManager\Tests;

use Barryvdh\TranslationManager\Manager;
use Barryvdh\TranslationManager\Models\Translation;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Filesystem\Filesystem;
use Mockery\MockInterface;

/**
 * @covers \Barryvdh\TranslationManager\Manager
 */
class ManagerTest extends TestCase
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

    public function testResolveManager(): void
    {
        $this->manager->makePartial();
        $this->assertIsArray($this->manager->getConfig());
    }

    public function testExport(): void
    {
        $this->manager->makePartial();
        $reflector = new \ReflectionObject($this->manager);
        $method = $reflector->getMethod('export');
        $result = $method->invoke($this->manager,['a'=> 'it\'s', 'b' => 'H\h', 'c' => '\d\e']);
        $this->assertEquals("[\n  'a' => 'it\'s',\n  'b' => 'H\h',\n  'c' => '\d\\e',\n]", $result);
    }

    public function testImportTranslations(): void
    {
        $this->dispatcher->shouldReceive('dispatch')->twice();
        $this->manager->makePartial();

        $translation = $this->mock(Translation::class);
        $builder = $this->mock(Builder::class);

        $keys = [
            ["locale" => 'en', 'group' => 'group0', 'key' => 'key0', 'value' => 'localized']
        ];

        $this->manager->shouldReceive("translation")->andReturn($translation)->once();
        $translation->shouldReceive('query')->andReturn($builder)->once();
        $builder->shouldReceive('insertOrIgnore')->withArgs([$keys]);

        $this->manager
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive("importTranslationBase")->andReturn($keys)->once();

        $this->manager->importTranslations();
    }

    public function testImportTranslationsReplacingKeys(): void
    {
        $this->dispatcher->shouldReceive('dispatch')->twice();
        $this->manager->makePartial();

        $translation = $this->mock(Translation::class);
        $builder = $this->mock(Builder::class);

        $keys = [
            ["locale" => 'en', 'group' => 'group0', 'key' => 'key0', 'value' => 'localized']
        ];

        $this->manager->shouldReceive("translation")->andReturn($translation)->once();
        $translation->shouldReceive('query')->andReturn($builder)->once();
        $builder->shouldReceive('upsert')->withArgs([$keys,['locale','group','key'],['value']]);

        $this->manager
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive("importTranslationBase")->andReturn($keys)->once();

        $this->manager->importTranslations(true);
    }

    public function testImportTranslationsSyncingKeys(): void
    {
        $this->dispatcher->shouldReceive('dispatch')->twice();
        $this->manager->makePartial();

        $translation = $this->mock(Translation::class);
        $builder = $this->mock(Builder::class);

        $keys = [
            ["locale" => 'en', 'group' => 'group0', 'key' => 'key0', 'value' => 'localized'],
            ["locale" => 'en', 'group' => 'group1', 'key' => 'key0', 'value' => 'localized'],
        ];

        $this->manager->shouldReceive("translation")->andReturn($translation)->twice();
        $translation->shouldReceive('query')->andReturn($builder)->twice();
        $builder->shouldReceive('whereIn')->withArgs(['group',['group0','group1']])->andReturn($builder)->once();
        $builder->shouldReceive('delete')->withNoArgs()->once();
        $builder->shouldReceive('insertOrIgnore')->withArgs([$keys])->once();

        $this->manager
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive("importTranslationBase")->andReturn($keys)->once();

        $this->manager->importTranslations(false,true);
    }

}
