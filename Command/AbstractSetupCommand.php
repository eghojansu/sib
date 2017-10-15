<?php

namespace Eghojansu\Bundle\SetupBundle\Command;

use Eghojansu\Bundle\SetupBundle\Service\Setup;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

abstract class AbstractSetupCommand extends ContainerAwareCommand
{
    /** @var string */
    protected $locale;

    /** @var Symfony\Component\Translation\TranslatorInterface */
    protected $translator;

    /** @var Symfony\Component\Console\Style\SymfonyStyle */
    protected $formatter;

    /** @var Eghojansu\Bundle\SetupBundle\Service\Setup */
    protected $setup;

    /** @var array selected version info */
    protected $version;

    /** @var boolean */
    protected $stop = false;


    protected function prepareSetupCommand(InputInterface $input, OutputInterface $output)
    {
        $this->formatter = new SymfonyStyle($input, $output);
        $this->locale = $input->getOption('locale');
        $this->setup = $this->getContainer()->get(Setup::class);

        return $this;
    }

    protected function trans($key, array $parameters = null, $domain = null)
    {
        if (empty($this->translator)) {
            $this->translator = $this->getContainer()->get('translator');
        }

        return $this->translator->trans($key, (array) $parameters, $domain, $this->locale);
    }

    protected function stop($stop = true)
    {
        $this->stop = $stop;
    }

    protected function askPassphrase()
    {
        $passphrase = $this->formatter->askHidden($this->trans('Please enter passphrase to continue'));
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

        $versions = $this->setup->getVersions($installableOnly);

        if (empty($versions)) {
            $this->formatter->note(
                $this->trans('No setup available')
            );
            $this->stop();

            return $this;
        }

        $headers = [
            $this->trans('No'),
            $this->trans('Version'),
            $this->trans('Description'),
            $this->trans('Installed'),
        ];
        $rows = [];

        $counter = 1;
        foreach ($versions as $key => $value) {
            $rows[] = [
                $counter++,
                $value['version'],
                $value['description'],
                $value['installed'] ? $this->trans('Installed') . " ($value[install_date])" : '~',
            ];
        }

        $this->formatter->table($headers, $rows);

        if (!$askVersion) {
            return $this;
        }

        $version = $this->formatter->choice(
            $this->trans('Please select version'),
            array_keys($versions)
        );

        if (!$version || empty($versions[$version])) {
            $this->formatter->error(
                $this->trans('Version %version% was not exists')
            );
            $this->stop();

            return $this;
        }

        $this->version = $versions[$version];

        return $this;
    }
}
