<?php

declare(strict_types=1);

/*
 * This file is part of Mindy Framework.
 * (c) 2017 Maxim Falaleev
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mindy\Bundle\MigrationBundle\Command;

use Doctrine\DBAL\Connection;
use Mindy\Component\MigrationManager\MigrationFactory;
use Mindy\Orm\ConnectionManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\HttpKernel\KernelInterface;

abstract class AbstractMigrationCommand extends Command
{
    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @var ConnectionManager
     */
    protected $connectionManager;

    /**
     * @var MigrationFactory
     */
    protected $migrationFactory;

    /**
     * AbstractMigrationCommand constructor.
     *
     * @param KernelInterface $kernel
     * @param ConnectionManager $connectionManager
     * @param MigrationFactory $migrationFactory
     */
    public function __construct(KernelInterface $kernel, ConnectionManager $connectionManager, MigrationFactory $migrationFactory)
    {
        $this->kernel = $kernel;
        $this->connectionManager = $connectionManager;
        $this->migrationFactory = $migrationFactory;

        parent::__construct();
    }

    /**
     * @param $name
     *
     * @return Connection
     */
    protected function getConnection($name): Connection
    {
        return $this->connectionManager->getConnection($name);
    }
}
