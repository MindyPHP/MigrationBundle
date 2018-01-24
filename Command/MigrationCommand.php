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

use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

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
        $target = $input->getOption('bundle');
        $bundles = empty($target) ? $this->kernel->getBundles() : [
            $this->kernel->getBundle($target),
        ];

        $connectionName = $input->getOption('connection');
        $connection = $this->getConnection($connectionName);

        $version = (string) $input->getArgument('version');
        $dryRun = (bool) $input->getOption('dry-run');
        $writeSql = $input->getOption('write-sql');

        foreach ($bundles as $name => $bundle) {
            /** @var BundleInterface $bundle */
            $migrationPath = sprintf('%s/Migrations', $bundle->getPath());
            if (false === is_dir($migrationPath)) {
                continue;
            }

            $output->writeln(sprintf('<info>%s</info>', $bundle->getName()));
            $manager = $this->migrationFactory->createManager(
                $connection,
                $bundle->getName(),
                $bundle->getPath(),
                $bundle->getNamespace()
            );

            $this->warningIfHasUnavailableMigrations($manager->getConfiguration(), $output);

            if ($writeSql) {
                $manager->writeSql($version, $writeSql);
            } else {
                $manager->doMigrate($version, $dryRun);
            }
        }

        /** @var BundleInterface $bundle */
        $migrationPath = sprintf('%s/Migrations', $this->kernel->getRootDir());
        if (is_dir($migrationPath)) {
            $output->writeln(sprintf('<info>Kernel %s</info>', $this->kernel->getName()));
            $kernelClass = get_class($this->kernel);
            $kernelNamespace = substr($kernelClass, 0, strrpos($kernelClass, '\\'));
            $manager = $this->migrationFactory->createManager(
                $connection,
                $this->kernel->getName(),
                $this->kernel->getRootDir(),
                $kernelNamespace
            );

            $this->warningIfHasUnavailableMigrations($manager->getConfiguration(), $output);

            if ($writeSql) {
                $manager->writeSql($version, $writeSql);
            } else {
                $manager->doMigrate($version, $dryRun);
            }
        }
    }

    /**
     * @param Configuration        $configuration
     * @param OutputInterface|null $output
     */
    protected function warningIfHasUnavailableMigrations(Configuration $configuration, OutputInterface $output = null)
    {
        $executedUnavailableMigrations = array_diff(
            $configuration->getMigratedVersions(),
            $configuration->getAvailableVersions()
        );

        if (!empty($executedUnavailableMigrations)) {
            $output->writeln(sprintf(
                '<error>WARNING! You have %s previously executed migrations'
                .' in the database that are not registered migrations.</error>',
                count($executedUnavailableMigrations)
            ));

            foreach ($executedUnavailableMigrations as $executedUnavailableMigration) {
                $output->writeln(sprintf(
                    '    <comment>>></comment> %s (<comment>%s</comment>)',
                    $configuration->getDateTime($executedUnavailableMigration),
                    $executedUnavailableMigration
                ));
            }
        }
    }
}
