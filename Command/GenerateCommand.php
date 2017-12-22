<?php

/*
 * This file is part of Mindy Framework.
 * (c) 2017 Maxim Falaleev
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mindy\Bundle\MigrationBundle\Command;

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
            ->addOption('bundle', 'b', InputOption::VALUE_OPTIONAL, 'Bundle name')
            ->addOption('template', null, InputOption::VALUE_OPTIONAL, 'Migration template.', __DIR__ . '/../Resources/migration/template.php.template')
            ->addOption('editor-cmd', null, InputOption::VALUE_OPTIONAL, 'Open file with this command upon creation.')
            ->addOption('connection', 'c', InputOption::VALUE_OPTIONAL, 'Connection name', 'default')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command generates a blank migration class:

    <info>%command.full_name%</info>

You can optionally specify a <comment>--editor-cmd</comment> option to open the generated file in your favorite editor:

    <info>%command.full_name% --editor-cmd=mate</info>
EOT
            );

        parent::configure();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $connectionName = $input->getOption('connection');
        $connection = $this->getConnection($connectionName);

        $target = $input->getOption('bundle');
        $bundle = $this->getKernel()->getBundle($target);

        $template = file_get_contents($input->getOption('template'));
        $path = $this
            ->getMigrationFactory()
            ->createManager($connection, $bundle)
            ->setInput($input)
            ->setOutput($output)
            ->generateMigration($template);

        $output->writeln(sprintf('Generated new migration class to "<info>%s</info>"', $path));
    }
}