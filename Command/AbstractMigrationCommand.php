<?php

/*
 * This file is part of Mindy Framework.
 * (c) 2017 Maxim Falaleev
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mindy\Bundle\MigrationBundle\Command;

use Doctrine\DBAL\Connection;
use Mindy\Bundle\MigrationBundle\MigrationManager\MigrationFactory;
use Mindy\Orm\ConnectionManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\HttpKernel\KernelInterface;

abstract class AbstractMigrationCommand extends ContainerAwareCommand
{
    /**
     * @return KernelInterface
     */
    protected function getKernel(): KernelInterface
    {
        return $this->getContainer()->get('kernel');
    }

    /**
     * @param $name
     * @return Connection
     */
    protected function getConnection($name): Connection
    {
        return $this->getConnectionManager()->getConnection($name);
    }

    /**
     * @return ConnectionManager
     */
    protected function getConnectionManager(): ConnectionManager
    {
        return $this->getContainer()->get('orm.connection_manager');
    }

    /**
     * @return MigrationFactory
     */
    protected function getMigrationFactory(): MigrationFactory
    {
        return $this->getContainer()->get('mindy.bundle.orm.migration_factory');
    }
}