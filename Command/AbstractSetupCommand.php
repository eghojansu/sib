<?php

namespace Eghojansu\Bundle\SetupBundle\Command;

use DateTime;
use Eghojansu\Bundle\SetupBundle\Service\Setup;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

abstract class AbstractSetupCommand extends ContainerAwareCommand
{
    /** @var Symfony\Component\Translation\TranslatorInterface */
    protected $translator;

    /** @var Symfony\Component\Console\Style\SymfonyStyle */
    protected $formatter;

    /** @var Eghojansu\Bundle\SetupBundle\Service\Setup */
    protected $setup;

    /** @var array supported options */
    protected $options = [
        'install-version' => null,
        'locale' => null,
        'passphrase' => null,
        'new-passphrase' => null,
        'confirmation' => null,
        'force' => false,
        'show-all' => false,
    ];

    /** @var boolean */
    protected $stop = false;

    /** @var boolean */
    protected $isInteractive = true;


    protected function prepareSetupCommand(
        InputInterface $input,
        OutputInterface $output
    ) {
        foreach ($this->options as $option => $value) {
            $this->options[$option] = $input->hasOption($option)?
                $input->getOption($option) : $value;
        }
        $this->isInteractive = !$input->getOption('no-interaction');
        $this->setup = $this->getContainer()->get(Setup::class);
        $this->translator = $this->getContainer()->get('translator');
        $this->formatter = new SymfonyStyle($input, $output);

        return $this;
    }

    protected function trans($key, array $parameters = null, $domain = null)
    {
        return $this->translator->trans(
            $key,
            (array) $parameters,
            $domain,
            $this->options['locale']
        );
    }

    protected function stop($stop = true)
    {
        $this->stop = $stop;
    }

    protected function askPassphrase()
    {
        if ($this->isInteractive) {
            $passphrase = $this->formatter->askHidden(
                $this->trans('Please enter passphrase to continue')
            );
        } else {
            $passphrase = $this->options['passphrase'];
        }

        if ($passphrase !== $this->setup->getPassphrase()) {
            $this->formatter->error(
                $this->trans('Wrong passphrase', null, 'validators')
            );
            $this->stop();
        }

        return $this;
    }

    protected function showVersionsOption($askVersion, $installableOnly)
    {
        if ($this->stop) {
            return $this;
        }

        $versions = $this->setup->getVersions(
            ($this->options['force'] || $this->options['show-all']) ?
                false : $installableOnly
        );

        if (empty($versions)) {
            $this->formatter->note(
                $this->trans('No setup available')
            );
            $this->stop();

            return $this;
        }

        if ($this->isInteractive || $this->options['show-all']) {
            $headers = [
                $this->trans('No'),
                $this->trans('Version'),
                $this->trans('Description'),
                $this->trans('Installed'),
            ];
            $rows = [];

            $counter = 1;
            foreach ($versions as $key => $value) {
                $status = '~';
                if ($value['installed']) {
                    $status = $this->trans('Installed');

                    if ($value['install_date']) {
                        $date = new DateTime($value['install_date']);
                        $status .= ' (' . $date->format('Y-m-d H:i:s') . ')';
                    }
                }

                $rows[] = [
                    $counter++,
                    $value['version'],
                    $value['description'],
                    $status,
                ];
            }

            $this->formatter->table($headers, $rows);
        }

        if (!$askVersion) {
            return $this;
        }

        $version = $this->options['install-version'];
        if ($this->isInteractive) {
            $version = $this->formatter->choice(
                $this->trans('Please select version'),
                array_keys($versions),
                $version
            );
        }


        if (!$version || empty($versions[$version])) {
            $this->formatter->error(
                $this->trans(
                    'Version %version% was not exists',
                    ['%version%'=>$version]
                )
            );
            $this->stop();

            return $this;
        }

        $this->options['install-version'] = $version;

        return $this;
    }
}
