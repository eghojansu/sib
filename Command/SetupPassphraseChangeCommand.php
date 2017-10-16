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
            ->addOption(
                'passphrase',
                null,
                InputOption::VALUE_REQUIRED,
                'Security passphrase'
            )
            ->addOption(
                'install-version',
                null,
                InputOption::VALUE_REQUIRED,
                'Version to install'
            )
            ->addOption(
                'new-passphrase',
                null,
                InputOption::VALUE_REQUIRED,
                'New passphrase'
            )
            ->addOption(
                'locale',
                null,
                InputOption::VALUE_REQUIRED,
                'Set locale',
                'en'
            )
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

        $version = $this->options['install-version'];
        if ($this->isInteractive) {
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

                $notMatch = $newPassphrase !== $repeatNewPassphrase;

                if ($notMatch) {
                    $this->formatter->error(
                        $this->trans('New passphrase doesn\'t match')
                    );
                }
            } while ($notMatch);
        } else {
            $newPassphrase = $this->options['new-passphrase'];
        }

        $this->setup->setPassphrase($version, $newPassphrase);

        $this->formatter->success(
            $this->trans('Passphrase has been updated')
        );

        return $this;
    }
}
