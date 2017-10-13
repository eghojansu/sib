Installation
============

Step 1: Download the Bundle
---------------------------

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require eghojansu/sib
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Step 2: Enable the Bundle
-------------------------

Then, enable the bundle by adding it to the list of registered bundles
in the `app/AppKernel.php` file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...
            new Eghojansu\Bundle\SetupBundle\EghojansuSetupBundle(),
        );

        // ...
    }

    // ...
}
```

Step 3: Load routes

```yaml
# ...

eghojansu_setup:
    resource: "@EghojansuSetupBundle/Resources/config/routing.yml"
    prefix:   /setup

# ...
```

Step 4: Provide Configuration
-----------------------------

This is a sampel configuration with default value. Better to separate this config in a file, eg: app/config/setup.yml
then include in main config file.

```yaml
# app/config/config.yml
imports:
    # ...
    - { resource: setup.yml }
    # ...
```

```yaml
# app/config/setup.yml
eghojansu_setup:
    # password to enter setup
    passphrase: "admin"

    # maintenance path, *you need to create your own controller to display maintenance status*
    maintenance_path: "/maintenance"

    # history path to save lock file
    history_path: "%kernel.project_dir%/var"

    # version list, you will update this list if there is a new update
    # and also update your setup listener/subscriber
    # note that version is required and its order doesnt matter
    versions:
        - version: "0.1.0" # version
          description: "First installation" # version description
          parameters: # parameters
            key: "parameters"
            destination: "%kernel.project_dir%/app/config/parameters.yml"
            sources:
                - "%kernel.project_dir%/app/config/parameters.yml.dist"
          config: # custom config you want to get from user
            my_option: # key is config name
                value: "two" # value is mandatory
                options: ["one","two","three"] # array of valid options, leave this empty if you doesnt want an combox input
                required: false # pass true if you require these config
                description: "you must fill this value" # if options not set, this will display in input placeholder
                group: "My Group" # your config will group by this value, leave empty to ungroup
        - version: "0.2.0" # this is another sampe of versions node
          description: |
            Long description with list of line
            - What a list
            - Of course these is a list
            - And this is the last item
          parameters: # if you dont want lose any config, always pass same parameters in each version
            sources:
                - "%kernel.project_dir%/app/config/parameters.yml.dist"

```

Step 5: Create your Setup Listener/Subscriber

This bundle will expose ```eghojansu_setup.events.post_config``` and pass ```Eghojansu\Bundle\SetupBundle\Event\SetupEvent``` event. You can listening to this event and do post process after you get configuration from user. Below is sampe of this listener.

```php
<?php

namespace AppBundle\EventListener;

use Eghojansu\Bundle\SetupBundle\Event\SetupEvent;
use Eghojansu\Bundle\SetupBundle\Service\CommandHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SetupSubscriber implements EventSubscriberInterface
{
    /** @var Eghojansu\Bundle\SetupBundle\Service\CommandHelper */
    private $command;

    public function __construct(CommandHelper $command)
    {
        $this->command = $command;
    }

    /**
     * {@inheritdoc}
    */
    public static function getSubscribedEvents()
    {
        return [
            SetupEvent::POST_CONFIG => 'onSetup'
        ];
    }

    public function onSetup(SetupEvent $event)
    {
        switch ($event->getVersion()) {
            case '0.1.0':
                $this->setup010();
                break;
            case '0.2.0':
                $this->setup020($event);
                break;
        }
    }

    private function setup010()
    {
        $this->command
            ->doctrineDatabaseDrop(['--if-exists'=>null,'--env'=>'prod'])
            ->doctrineDatabaseCreate(['--if-not-exists'=>null,'--env'=>'prod'])
            ->doctrineSchemaCreate(['--force'=>null,'--env'=>'prod'])
        ;
    }

    private function setup020(SetupEvent $event)
    {
        $this->command->doctrineDatabaseImport([
            'file' => $event->getParameter('kernel.project_dir') . '/app/database/import.sql',
        ]);
        $event->setMessage('Thank you'); // this message will displayed after setup successfully performed
    }
}

```

In example above, you see a helper class called ```Eghojansu\Bundle\SetupBundle\Service\CommandHelper``` which is a helper to execute console command that registered in your application. Shortly its like perform command in your ```bin/console```. See example below.

```php

// ...

/** @var Eghojansu\Bundle\SetupBundle\Service\CommandHelper */
private $command;

// ...
$this->command->doctrineDatabaseCreate();
// above command will execute doctrine:database:create command
// and you can pass an array as arguments which key is the arguments/options name and its value
// like this
// $this->command->doctrineDatabaseCreate(['--if-not-exists'=>null])

// ...
```
