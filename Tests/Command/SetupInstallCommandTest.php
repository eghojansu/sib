<?php

namespace Eghojansu\Bundle\SetupBundle\Tests\Command;

use TestHelper;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SetupInstallCommandTest extends KernelTestCase
{
    protected function setUp()
    {
        TestHelper::prepare();
    }

    public function testExecute()
    {
        static::bootKernel();

        $application = new Application(self::$kernel);

        $command = $application->find('setup:install');
        $commandTester = new CommandTester($command);

        $commandTester->setInputs([
            'welcome',
            '0.1.0',
            'console_modified',
            'console_modified',
            'two',
            'console_modified',
            'console_modified',
            '%kernel.root_dir%/var/data.sqlite',
            'ThisTokenIsNotSoSecretChangeIt',
            'CONFIRM',
        ]);

        $commandTester->execute([
            'command' => $command->getName(),
            '--locale' => 'en',
        ]);

        $output = $commandTester->getDisplay();

        $this->assertContains('Please enter passphrase to continue', $output);
    }
}
