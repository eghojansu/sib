<?php

namespace Eghojansu\Bundle\SetupBundle\Tests\Command;

use DateTime;
use TestHelper;
use Eghojansu\Bundle\SetupBundle\Service\Setup;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SetupInstallCommandTest extends KernelTestCase
{
    /** @var Symfony\Bundle\FrameworkBundle\Console\Application */
    private $console;

    protected function setUp()
    {
        TestHelper::prepare();

        static::bootKernel();

        $this->console = new Application(self::$kernel);
    }

    public function testExecute()
    {
        $command = $this->console->find('setup:install');
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

        $setup = self::$kernel->getContainer()->get(Setup::class);
        $this->assertEquals('ThisTokenIsNotSoSecretChangeIt', $setup->getParameter('secret'));
    }

    public function testExecuteWithForceOption()
    {
        TestHelper::setYamlContent(TestHelper::varfilepath(Setup::HISTORY_FILENAME), [
            [
                'version' => '0.1.0',
                'date' => new DateTime(),
                'ip' => null,
                'agent' => null,
            ]
        ], 'installed');

        $command = $this->console->find('setup:install');
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
            '--force' => null,
        ]);

        $output = $commandTester->getDisplay();

        $this->assertContains('Please enter passphrase to continue', $output);

        $setup = self::$kernel->getContainer()->get(Setup::class);
        $this->assertEquals('ThisTokenIsNotSoSecretChangeIt', $setup->getParameter('secret'));
    }

    public function testExecuteWithoutInteraction()
    {
        $command = $this->console->find('setup:install');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'command' => $command->getName(),
            '--locale' => 'en',
            '--passphrase' => 'welcome',
            '--confirmation' => 'CONFIRM',
            '--sversion' => '0.1.0',
            '--no-interaction' => null,
        ]);

        $output = $commandTester->getDisplay();

        $this->assertContains('Installation of 0.1.0 version has been performed', $output);

        $setup = self::$kernel->getContainer()->get(Setup::class);
        $this->assertEquals('ThisTokenIsNotSoSecretChangeIt', $setup->getParameter('secret'));
    }

    public function testExecuteWithoutInteractionWithForceOption()
    {
        TestHelper::setYamlContent(TestHelper::varfilepath(Setup::HISTORY_FILENAME), [
            [
                'version' => '0.1.0',
                'date' => new DateTime(),
                'ip' => null,
                'agent' => null,
            ]
        ], 'installed');
        $command = $this->console->find('setup:install');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'command' => $command->getName(),
            '--locale' => 'en',
            '--passphrase' => 'welcome',
            '--confirmation' => 'CONFIRM',
            '--sversion' => '0.1.0',
            '--no-interaction' => null,
            '--force'=>null,
        ]);

        $output = $commandTester->getDisplay();

        $this->assertContains('Installation of 0.1.0 version has been performed', $output);

        $setup = self::$kernel->getContainer()->get(Setup::class);
        $this->assertEquals('ThisTokenIsNotSoSecretChangeIt', $setup->getParameter('secret'));
    }
}
