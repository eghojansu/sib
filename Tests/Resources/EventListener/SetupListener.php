<?php

namespace Eghojansu\Bundle\SetupBundle\Tests\Resources\EventListener;

use DateTime;
use Eghojansu\Bundle\SetupBundle\Event\SetupEvent;
use Eghojansu\Bundle\SetupBundle\Service\CommandHelper;

class SetupListener
{
    /** @var Eghojansu\Bundle\SetupBundle\Service\CommandHelper */
    private $command;

    public function __construct(CommandHelper $command)
    {
        $this->command = $command;
    }

    public function onSetup(SetupEvent $event)
    {
        switch ($event->getVersion()) {
            case '0.1.0':
                $this->setup010($event);
                break;
        }
    }

    private function setup010(SetupEvent $event)
    {
        $test_file = __DIR__ . '/../../var/created_by_setup_listener.txt';
        file_put_contents($test_file, new DateTime());

        $this->command
            ->doctrineDatabaseCreate(['--env'=>'prod'])
        ;
    }
}
