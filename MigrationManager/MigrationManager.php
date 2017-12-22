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
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Migration;
use Doctrine\DBAL\Migrations\Tools\Console\Helper\MigrationDirectoryHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class MigrationManager
{
    /**
     * @var Configuration
     */
    protected $configuration;
    /**
     * @var InputInterface
     */
    protected $input;
    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * MigrationManager constructor.
     * @param Connection $connection
     * @param BundleInterface $bundle
     */
    public function __construct(Connection $connection, BundleInterface $bundle)
    {
        $this->configuration = $this->doBuildConfiguration($connection, $bundle);
    }

    /**
     * @param InputInterface $input
     * @return $this
     */
    public function setInput(InputInterface $input)
    {
        $this->input = $input;

        return $this;
    }

    /**
     * @param OutputInterface $output
     *
     * @return $this
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;

        return $this;
    }

    /**
     * Create a new migration instance to execute the migrations.
     *
     * @return Migration a new migration instance
     */
    protected function createMigration(): Migration
    {
        return new Migration($this->configuration);
    }

    /**
     * @param string $version
     * @param bool|string $path
     * @return int
     */
    public function writeSql(string $version, $path)
    {
        return $this->createMigration()->writeSqlFile(
            is_bool($path) ? getcwd() : $path,
            $this->diffMigrations($version)
        );
    }

    /**
     * @param string $version
     *
     * @return string
     */
    protected function diffMigrations(string $version)
    {
        $executedMigrations = $this->configuration->getMigratedVersions();
        $availableMigrations = $this->configuration->getAvailableVersions();

        $version = $this->getVersionNameFromAlias($version);
        if ($version === false) {
            return false;
        }

        $executedUnavailableMigrations = array_diff($executedMigrations, $availableMigrations);
        if (!empty($executedUnavailableMigrations)) {
            $this->output->writeln(sprintf(
                '<error>WARNING! You have %s previously executed migrations'
                . ' in the database that are not registered migrations.</error>',
                count($executedUnavailableMigrations)
            ));

            foreach ($executedUnavailableMigrations as $executedUnavailableMigration) {
                $this->output->writeln(sprintf(
                    '    <comment>>></comment> %s (<comment>%s</comment>)',
                    $this->configuration->getDateTime($executedUnavailableMigration),
                    $executedUnavailableMigration
                ));
            }
        }

        return $version;
    }

    /**
     * @param string $version
     * @param bool $dryRun
     *
     * @return bool
     */
    public function doMigrate(string $version, bool $dryRun): bool
    {
        $migration = $this->createMigration();
        $migration->setNoMigrationException(true);
        $migration->migrate($this->diffMigrations($version), $dryRun, true);

        return true;
    }

    /**
     * @param Connection $connection
     * @param BundleInterface $bundle
     *
     * @return Configuration
     */
    public function doBuildConfiguration(Connection $connection, BundleInterface $bundle): Configuration
    {
        $configuration = new Configuration($connection);
        $configuration->setName($bundle->getName());
        $configuration->setMigrationsTableName($this->doNormalizeName($bundle->getName()));

        $reflect = new \ReflectionClass($bundle);
        $configuration->setMigrationsNamespace(sprintf(
            '%s\Migrations',
            $reflect->getNamespaceName()
        ));

        $migrationPath = sprintf('%s/Migrations', $bundle->getPath());
        $this->createDirIfNotExists($migrationPath);

        $configuration->setMigrationsDirectory($migrationPath);

        return $configuration;
    }

    /**
     * @param string $versionAlias
     *
     * @return string
     */
    private function getVersionNameFromAlias($versionAlias): string
    {
        $version = $this->configuration->resolveVersionAlias($versionAlias);
        if ($version === null) {
            if ($versionAlias == 'prev') {
                throw new \RuntimeException('Already at first version.');
            }
            if ($versionAlias == 'next') {
                throw new \RuntimeException('Already at latest version.');
            }

            throw new \RuntimeException(sprintf(
                'Unknown version: %s',
                $versionAlias
            ));
        }

        return $version;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function doNormalizeName($name)
    {
        $cleanName = str_replace('Bundle', '', $name);
        $normalizedName = trim(strtolower(preg_replace('/(?<![A-Z])[A-Z]/', '_\0', $cleanName)), '_');

        return sprintf('%s_migrations', $normalizedName);
    }

    /**
     * @return Configuration
     */
    public function getConfiguration(): Configuration
    {
        return $this->configuration;
    }

    /**
     * @return string
     */
    public function getMigrationDirectory(): string
    {
        $dir = $this->configuration->getMigrationsDirectory();
        $dir = $dir ? $dir : getcwd();
        $dir = rtrim($dir, '/');

        if (!file_exists($dir)) {
            throw new \InvalidArgumentException(sprintf('Migrations directory "%s" does not exist.', $dir));
        }

        if ($this->configuration->areMigrationsOrganizedByYear()) {
            $dir .= $this->appendDir(date('Y'));
        }

        if ($this->configuration->areMigrationsOrganizedByYearAndMonth()) {
            $dir .= $this->appendDir(date('m'));
        }
        $this->createDirIfNotExists($dir);

        return $dir;
    }

    /**
     * @param $dir
     * @return string
     */
    private function appendDir($dir)
    {
        return DIRECTORY_SEPARATOR . $dir;
    }

    /**
     * @param $dir
     */
    private function createDirIfNotExists($dir)
    {
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    /**
     * @param string $template
     * @param null $up
     * @param null $down
     * @return string
     */
    public function generateMigration(string $template, $up = null, $down = null)
    {
        $version = $this->configuration->generateVersionNumber();
        $placeHolders = [
            '<namespace>',
            '<version>',
            '<up>',
            '<down>',
        ];
        $replacements = [
            $this->configuration->getMigrationsNamespace(),
            $version,
            $up ? "        " . implode("\n        ", explode("\n", $up)) : null,
            $down ? "        " . implode("\n        ", explode("\n", $down)) : null
        ];
        $code = str_replace($placeHolders, $replacements, $template);
        $code = preg_replace('/^ +$/m', '', $code);
        $path = $this->getMigrationDirectory() . '/Version' . $version . '.php';

        file_put_contents($path, $code);

        if ($editorCmd = $this->input->getOption('editor-cmd')) {
            proc_open($editorCmd . ' ' . escapeshellarg($path), [], $pipes);
        }

        return $path;
    }
}