<?php

declare(strict_types=1);

/*
 * This file is part of Mindy Framework.
 * (c) 2017 Maxim Falaleev
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
            'connections' => [
                'default' => ['url' => 'sqlite:///:memory:']
            ]
        ]);

        $this->migrationFactory = new MigrationFactory();
    }

    protected static function createKernel(array $options = array())
    {
        return new TestKernel('dev', true);
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
            'bundle' => 'TestBundle'
        ]);

        $this->assertContains('Generated new migration class to', $commandTester->getDisplay());
    }
}
