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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCommand extends AbstractMigrationCommand
{
    protected function configure()
    {
        $this
            ->setName('orm:migration:generate')
            ->setDescription('Generate a blank migration class.')
            ->addArgument('bundle', InputArgument::OPTIONAL, 'Bundle name')
            ->addOption('template', null, InputOption::VALUE_OPTIONAL, 'Migration template.')
            ->addOption('editor-cmd', null, InputOption::VALUE_OPTIONAL, 'Open file with this command upon creation.')
            ->addOption('connection', 'c', InputOption::VALUE_OPTIONAL, 'Connection name', 'default')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command generates a blank migration class:

    <info>%command.full_name%</info>

You can optionally specify a <comment>--editor-cmd</comment> option to open the generated file in your favorite editor:

    <info>%command.full_name% --editor-cmd=mate</info>
EOT
            );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $connectionName = $input->getOption('connection');
        $connection = $this->getConnection($connectionName);

        $template = null;
        if ($templatePath = $input->getOption('template')) {
            $template = file_get_contents($templatePath);
        }

        $bundleName = $input->getArgument('bundle');
        if (false === empty($bundleName)) {
            $bundle = $this->kernel->getBundle($bundleName);

            $path = $this->migrationFactory->createManager(
                $connection,
                $bundle->getName(),
                $bundle->getPath(),
                $bundle->getNamespace()
            )->generateMigration($template);
        } else {
            $kernelClass = get_class($this->kernel);
            $kernelNamespace = substr($kernelClass, 0, strrpos($kernelClass, '\\'));
            $path = $this->migrationFactory->createManager(
                $connection,
                $this->kernel->getName(),
                $this->kernel->getRootDir(),
                $kernelNamespace
            )->generateMigration($template);
        }

        $output->writeln(sprintf(
            'Generated new migration class to "<info>%s</info>"',
            $path
        ));

        if ($editorCmd = $input->getOption('editor-cmd')) {
            proc_open($editorCmd.' '.escapeshellarg($path), [], $pipes);
        }
    }
}
