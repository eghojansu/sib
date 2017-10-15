<?php

namespace Eghojansu\Bundle\SetupBundle\Command;

use Symfony\Component\Console\Helper\Table;
use Eghojansu\Bundle\SetupBundle\Service\Setup;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Eghojansu\Bundle\SetupBundle\Event\SetupEvent;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class SetupInstallCommand extends ContainerAwareCommand
{
    /** @var string */
    private $locale;

    /** @var Symfony\Component\Translation\TranslatorInterface */
    private $translator;

    /** @var Symfony\Component\Console\Style\SymfonyStyle */
    private $formatter;

    /** @var Eghojansu\Bundle\SetupBundle\Service\Setup */
    private $setup;

    /** @var array version info to install */
    private $version;

    /** @var boolean */
    private $stop = false;

    /** @var array */
    private $submitted = [];


    protected function configure()
    {
        $this
            ->setName('setup:install')
            ->setDescription('Interactive install interface')
            ->addOption('locale', null, InputOption::VALUE_REQUIRED, 'Set locale', 'en')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->locale = $input->getOption('locale');
        $this->setup = $this->getContainer()->get(Setup::class);
        $this->formatter = new SymfonyStyle($input, $output);

        // begin interface
        $this
            ->askPassphrase()
            ->showVersionsOption()
            ->askConfig()
            ->confirmSetup()
        ;
    }

    private function trans($key, array $parameters = null, $domain = null)
    {
        if (empty($this->translator)) {
            $this->translator = $this->getContainer()->get('translator');
        }

        return $this->translator->trans($key, (array) $parameters, $domain, $this->locale);
    }

    private function stop($stop = true)
    {
        $this->stop = $stop;
    }

    private function warningBlock()
    {
        $message = implode(PHP_EOL, array_filter(func_get_args()));
        $this->formatter->warning($message);

        return $this;
    }

    private function errorBlock()
    {
        $message = implode(PHP_EOL, array_filter(func_get_args()));
        $this->formatter->error($message);

        return $this;
    }

    private function infoBlock()
    {
        $message = implode(PHP_EOL, array_filter(func_get_args()));
        $this->formatter->note($message);

        return $this;
    }

    private function successBlock()
    {
        $message = implode(PHP_EOL, array_filter(func_get_args()));
        $this->formatter->success($message);

        return $this;
    }

    private function askPassphrase()
    {
        $passphrase = $this->formatter->askHidden($this->trans('Please enter passphrase to continue'));
        if ($passphrase !== $this->setup->getConfig('passphrase')) {
            $this->errorBlock(
                $this->trans('Wrong passphrase', null, 'validators')
            )->stop();
        }

        return $this;
    }

    private function showVersionsOption()
    {
        if ($this->stop) {
            return $this;
        }

        $versions = $this->setup->getVersions(true);

        if (empty($versions)) {
            $this->infoBlock(
                $this->trans('No setup available')
            )->stop();

            return $this;
        }

        $headers = [
            $this->trans('No'),
            $this->trans('Version'),
            $this->trans('Description'),
        ];
        $rows = [];

        $counter = 1;
        foreach ($versions as $key => $value) {
            $rows[] = [
                $counter++,
                $value['version'],
                $value['description'],
            ];
        }

        $this->formatter->table($headers, $rows);

        $version = $this->formatter->choice(
            $this->trans('Please select version to install'),
            array_keys($versions)
        );

        if (!$version || empty($versions[$version])) {
            $this->errorBlock(
                $this->trans('Version %version% was not exists')
            )->stop();

            return $this;
        }

        $this->version = $versions[$version];

        return $this;
    }

    private function askConfig()
    {
        if ($this->stop) {
            return $this;
        }

        $this->formatter->text($this->trans('Please fill configuration below'));

        foreach ($this->version['config'] as $cName => $cVal) {
            $value = $this->setup->getParameter($cName, $cVal['value']);

            if ($cVal['options']) {
                $this->submitted[$cName] = $this->formatter->choice(
                    $cName,
                    $cVal['options'],
                    $value
                );
            } else {
                if ($cVal['description']) {
                    $cName .= " ($cVal[description])";
                }
                $this->submitted[$cName] = $this->formatter->ask($cName, $value);
            }
        }

        foreach ($this->version['parameters']['sources'] as $key => $file) {
            $content = $this->setup->getYamlContent($file, $this->version['parameters']['key']);

            foreach ($content as $parameter => $value) {
                $value = $this->setup->getParameter($parameter, $value);

                $this->submitted[$parameter] = $this->formatter->ask($parameter, $value);
            }
        }

        return $this;
    }

    private function confirmSetup()
    {
        if ($this->stop) {
            return $this;
        }

        $confirmation = 'CONFIRM';
        $cancelation = 'CANCEL';
        $question = $this->trans('You will install version %version% (type %confirm% to confirm, type %cancel% to cancel)', [
            '%version%' => $this->version['version'],
            '%confirm%' => $confirmation,
            '%cancel%' => $cancelation,
        ]);
        do {
            $answer = $this->formatter->ask($question);
        } while (!in_array($answer, [$confirmation, $cancelation]));

        if ($answer == $cancelation) {
            $this->warningBlock(
                $this->trans('Installation canceled')
            )->stop();

            return $this;
        }

        $this->formatter->note($this->trans('Maintenance mode will be active when during setup'));

        $this->setup->setMaintenance(true);
        $this->setup->updateParameters($this->version['version'], $this->submitted);

        $event = $this->getContainer()->get(SetupEvent::class);
        $event->setVersion($this->version['version']);

        $eventDispatcher = $this->getContainer()->get('debug.event_dispatcher');
        $eventDispatcher->dispatch(SetupEvent::POST_CONFIG, $event);

        $this->setup->recordSetupHistory($this->version['version']);

        $systemMessage = $this->trans('Installation of %version% version has been performed', [
            '%version%'=>$this->version['version']
        ]);
        $devMessage = $event->getMessage();

        $this->successBlock(
            $systemMessage,
            $devMessage
        );
        $this->setup->setMaintenance(false);

        return $this;
    }
}
