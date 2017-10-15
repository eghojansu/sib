<?php

namespace Eghojansu\Bundle\SetupBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SetupPassphraseChangeCommand extends AbstractSetupCommand
{
    protected function configure()
    {
        $this
            ->setName('setup:passphrase:change')
            ->setDescription('Change passphrase')
            ->addOption('passphrase', null, InputOption::VALUE_REQUIRED, 'Security passphrase')
            ->addOption('sversion', null, InputOption::VALUE_REQUIRED, 'Selected version')
            ->addOption('new-passphrase', null, InputOption::VALUE_REQUIRED, 'New passphrase')
            ->addOption('locale', null, InputOption::VALUE_REQUIRED, 'Set locale', 'en')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this
            ->prepareSetupCommand($input, $output)
            ->askPassphrase()
            ->showVersionsOption(true, true)
            ->askNewPassphrase()
        ;
    }

    private function askNewPassphrase()
    {
        if ($this->stop) {
            return $this;
        }

        if ($this->noInteraction) {
            $newPassphrase = $this->myInput->getOption('new-passphrase');
        } else {
            do {
                $this->formatter->comment(
                    $this->trans('Press Ctrl+C to cancel')
                );

                $newPassphrase = $this->formatter->askHidden(
                    $this->trans('New passphrase')
                );
                $repeatNewPassphrase = $this->formatter->askHidden(
                    $this->trans('Repeat new passphrase')
                );

                $continue = $newPassphrase === $repeatNewPassphrase;

                if (!$continue) {
                    $this->formatter->error(
                        $this->trans('New passphrase doesn\'t match')
                    );
                }
            } while (!$continue);
        }

        $this->setup->setPassphrase($this->version['version'], $newPassphrase);

        $message = $this->trans('Passphrase has been updated');

        $this->formatter->success($message);

        return $this;
    }
}
