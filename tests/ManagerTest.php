<?php

namespace Barryvdh\TranslationManager\Tests;

use Barryvdh\TranslationManager\Manager;

class ManagerTest extends TestCase
{
    public function testResolveManager(): void
    {
        /** @var Manager $manager */
        $manager = app(Manager::class);

        $this->assertIsArray($manager->getConfig());
    }

    public function testExport(): void
    {
        /** @var Manager $manager */
        $manager = app(Manager::class);

        $reflector = new \ReflectionObject($manager);
        $method = $reflector->getMethod('export');

        $result = $method->invoke($manager,['a'=> 'it\'s', 'b' => 'H\h', 'c' => '\d\e']);

        $this->assertEquals("[\n  'a' => 'it\'s',\n  'b' => 'H\h',\n  'c' => '\d\\e',\n]", $result);
    }

}
