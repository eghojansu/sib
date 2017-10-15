<?php

namespace Eghojansu\Bundle\SetupBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Eghojansu\Bundle\SetupBundle\Event\SetupEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SetupInstallCommand extends AbstractSetupCommand
{
    /** @var array */
    private $submitted = [];


    protected function configure()
    {
        $this
            ->setName('setup:install')
            ->setDescription('Interactive install interface')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force install')
            ->addOption('passphrase', null, InputOption::VALUE_REQUIRED, 'Security passphrase')
            ->addOption('sversion', null, InputOption::VALUE_REQUIRED, 'Selected version')
            ->addOption('confirmation', null, InputOption::VALUE_REQUIRED, 'Confirmation')
            ->addOption('locale', null, InputOption::VALUE_REQUIRED, 'Set locale', 'en')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this
            ->prepareSetupCommand($input, $output)
            ->askPassphrase()
            ->showVersionsOption(true, true)
            ->askConfig()
            ->confirmSetup()
        ;
    }

    private function askConfig()
    {
        if ($this->stop) {
            return $this;
        }

        $this->formatter->text($this->trans('Please fill configuration below'));

        foreach ($this->version['config'] as $cName => $cVal) {
            $value = $this->setup->getParameter($cName, $cVal['value']);

            if ($this->noInteraction) {
                $this->submitted[$cName] = $value;
                continue;
            }

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
                if (!$this->setup->isConfigAllowedInParameters($parameter)) {
                    continue;
                }

                $value = $this->setup->getParameter($parameter, $value);

                if ($this->noInteraction) {
                    $this->submitted[$parameter] = $value;
                    continue;
                }

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
        if ($this->noInteraction) {
            $answer = $this->myInput->getOption('confirmation');
        } else {
            $question = $this->trans('You will install version %version% (type %confirm% to confirm, type %cancel% to cancel)', [
                '%version%' => $this->version['version'],
                '%confirm%' => $confirmation,
                '%cancel%' => $cancelation,
            ]);
            do {
                $answer = $this->formatter->ask($question);
            } while (!in_array($answer, [$confirmation, $cancelation]));
        }

        if ($answer == $cancelation) {
            $this->formatter->warning(
                $this->trans('Installation canceled')
            );
            $this->stop();

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

        $message = $this->trans('Installation of %version% version has been performed', [
            '%version%'=>$this->version['version']
        ]);
        $devMessage = $event->getMessage();
        if ($devMessage) {
            $message .= PHP_EOL . $devMessage;
        }

        $this->formatter->success($message);

        $this->setup->setMaintenance(false);

        return $this;
    }
}
