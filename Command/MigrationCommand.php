<?php

/*
 * This file is part of Mindy Framework.
 * (c) 2017 Maxim Falaleev
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mindy\Bundle\MigrationBundle\Command;

use Mindy\Bundle\MigrationBundle\MigrationManager\MigrationFactory;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class MigrationCommand extends AbstractMigrationCommand
{
    protected function configure()
    {
        $this
            ->setName('orm:migration:migrate')
            ->addArgument('version', InputArgument::OPTIONAL, 'The version number (YYYYMMDDHHMMSS) or alias (first, prev, next, latest) to migrate to.', 'latest')
            ->addOption('bundle', 'b', InputOption::VALUE_OPTIONAL, 'Bundle name')
            ->addOption('write-sql', null, InputOption::VALUE_NONE, 'The path to output the migration SQL file instead of executing it.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Execute the migration as a dry run.')
            ->addOption('connection', 'c', InputOption::VALUE_OPTIONAL, 'Connection name', 'default');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $kernel = $this->getKernel();

        $target = $input->getOption('bundle');
        $bundles = empty($target) ? $kernel->getBundles() : [
            $kernel->getBundle($target)
        ];

        $connectionName = $input->getOption('connection');
        $connection = $this->getConnection($connectionName);

        $version = (string)$input->getArgument('version');
        $dryRun = (boolean)$input->getOption('dry-run');
        $writeSql = $input->getOption('write-sql');

        $factory = $this->getMigrationFactory();

        foreach ($bundles as $name => $bundle) {
            /** @var BundleInterface $bundle */
            $migrationPath = sprintf("%s/Migrations", $bundle->getPath());
            if (false === is_dir($migrationPath)) {
                continue;
            }

            $output->writeln(sprintf('<info>%s</info>', $bundle->getName()));
            $manager = $factory
                ->createManager($connection, $bundle)
                ->setInput($input)
                ->setOutput($output);

            if ($writeSql) {
                $manager->writeSql($version, $writeSql);
            } else {
                $manager->doMigrate($version, $dryRun);
            }
        }
    }
}