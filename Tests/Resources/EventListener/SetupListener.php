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
                $this->setup010();
                break;
        }
    }

    private function setup010()
    {
        $test_file = TestHelper::varfilepath(TestHelper::FILE_BY_LISTENER);
        file_put_contents($test_file, new DateTime());
    }
}
