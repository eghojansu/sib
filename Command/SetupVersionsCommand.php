<?php

namespace Eghojansu\Bundle\SetupBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SetupVersionsCommand extends AbstractSetupCommand
{
    protected function configure()
    {
        $this
            ->setName('setup:versions')
            ->setDescription('Show available versions to install')
            ->addOption('passphrase', null, InputOption::VALUE_REQUIRED, 'Security passphrase')
            ->addOption('locale', null, InputOption::VALUE_REQUIRED, 'Set locale', 'en')
            ->addOption('show-all', null, InputOption::VALUE_NONE, 'Show installed versions to')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $installableOnly = !$input->getOption('show-all');
        $this
            ->prepareSetupCommand($input, $output)
            ->askPassphrase()
            ->showVersionsOption(false, $installableOnly)
        ;
    }
}
