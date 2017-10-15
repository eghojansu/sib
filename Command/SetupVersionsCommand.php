<?php

namespace Eghojansu\Bundle\SetupBundle\Command;

use Eghojansu\Bundle\SetupBundle\Service\Setup;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class SetupVersionsCommand extends ContainerAwareCommand
{
    /** @var string */
    private $locale;

    /** @var Symfony\Component\Translation\TranslatorInterface */
    private $translator;

    protected function configure()
    {
        $this
            ->setName('setup:versions')
            ->setDescription('Show available versions to install')
            ->addOption('locale', null, InputOption::VALUE_REQUIRED, 'Set locale', 'en')
            ->addOption('show-all', null, InputOption::VALUE_NONE, 'Show installed versions to')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->locale = $input->getOption('locale');
        $installableOnly = !$input->getOption('show-all');
        /** @var Symfony\Component\Console\Style\SymfonyStyle */
        $formatter = new SymfonyStyle($input, $output);

        $setup = $this->getContainer()->get(Setup::class);
        $versions = $setup->getVersions($installableOnly);

        $headers = [
            $this->trans('No'),
            $this->trans('Version'),
            $this->trans('Description'),
            $this->trans('Action'),
        ];
        $rows = [];

        $counter = 1;
        foreach ($versions as $key => $value) {
            $rows[] = [
                $counter++,
                $value['version'],
                $value['description'],
                $value['installed'] ? $this->trans('Installed') : null
            ];
        }

        $formatter->table($headers, $rows);
    }

    private function trans($key, array $parameters = null, $domain = null)
    {
        if (empty($this->translator)) {
            $this->translator = $this->getContainer()->get('translator');
        }

        return $this->translator->trans($key, (array) $parameters, $domain, $this->locale);
    }
}
