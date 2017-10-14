<?php

namespace Eghojansu\Bundle\SetupBundle\Tests\Resources\EventListener;

use TestHelper;
use DateTime;
use Eghojansu\Bundle\SetupBundle\Event\SetupEvent;

class SetupListener
{
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
        $test_file = TestHelper::varfilepath(TestHelper::FILE_BY_LISTENER);
        file_put_contents($test_file, $event->getParameter('custom_value'));
    }
}
