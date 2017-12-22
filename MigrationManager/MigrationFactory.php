<?php

/*
 * This file is part of Mindy Framework.
 * (c) 2017 Maxim Falaleev
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mindy\Bundle\MigrationBundle\MigrationManager;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class MigrationFactory
{
    /**
     * @param Connection $connection
     * @param BundleInterface $bundle
     * @return MigrationManager
     */
    public function createManager(Connection $connection, BundleInterface $bundle): MigrationManager
    {
        return new MigrationManager($connection, $bundle);
    }
}