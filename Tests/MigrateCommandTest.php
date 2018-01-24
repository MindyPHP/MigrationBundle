<?php

declare(strict_types=1);

/*
 * Studio 107 (c) 2018 Maxim Falaleev
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mindy\Bundle\MigrationBundle\Tests;

use Mindy\Bundle\MigrationBundle\Command\GenerateCommand;
use Mindy\Bundle\MigrationBundle\Command\MigrationCommand;
use Mindy\Component\MigrationManager\MigrationFactory;
use Mindy\Orm\ConnectionManager;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class MigrateCommandTest extends KernelTestCase
{
    /**
     * @var ConnectionManager
     */
    protected $connectionManager;

    /**
     * @var MigrationFactory
     */
    protected $migrationFactory;

    protected function setUp()
    {
        $this->connectionManager = new ConnectionManager([
            'default' => ['url' => 'sqlite:///:memory:'],
        ]);

        $this->migrationFactory = new MigrationFactory();
    }

    protected static function createKernel(array $options = [])
    {
        return new TestKernel('dev', true);
    }

    protected function tearDown()
    {
        (new Filesystem())->remove([
            __DIR__.'/TestBundle/Migrations',
            __DIR__.'/Migrations',
            __DIR__.'/var'
        ]);
    }

    protected function getApp()
    {
        $kernel = static::createKernel();
        $kernel->boot();

        return new Application($kernel);
    }

    public function testKernel()
    {
        $bundles = $this->getApp()->getKernel()->getBundles();
        $this->assertSame(['TestBundle'], array_keys($bundles));
    }

    public function testMigrationCommand()
    {
        $app = $this->getApp();
        $app->add(
            new MigrationCommand(
                $app->getKernel(),
                $this->connectionManager,
                $this->migrationFactory
            )
        );

        $command = $app->find('orm:migration:migrate');

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ], [
            'dry-run' => true,
        ]);

        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function testGenerateCommand()
    {
        $app = $this->getApp();

        $app->add(
            new GenerateCommand(
                $app->getKernel(),
                $this->connectionManager,
                $this->migrationFactory
            )
        );

        $command = $app->find('orm:migration:generate');

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'bundle' => 'TestBundle',
        ]);
        $this->assertContains('Generated new migration class to', $commandTester->getDisplay(true));
        $this->assertCount(1, Finder::create()->files()->in(__DIR__.'/TestBundle/Migrations'));

        $commandTester->execute([
            'command' => $command->getName(),
        ]);
        $this->assertContains('Generated new migration class to', $commandTester->getDisplay(true));
        $this->assertCount(1, Finder::create()->files()->in(__DIR__.'/Migrations'));
    }
}
