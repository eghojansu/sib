<?php

namespace Eghojansu\Bundle\SetupBundle\Tests\Command;

use TestHelper;
use Eghojansu\Bundle\SetupBundle\Service\Setup;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SetupPassphraseChangeCommandTest extends KernelTestCase
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
        $command = $this->console->find('setup:passphrase:change');
        $commandTester = new CommandTester($command);

        $commandTester->setInputs([
            'welcome',
            '0.1.0',
            'changex',
            'changex',
        ]);

        $commandTester->execute([
            'command' => $command->getName(),
            '--locale' => 'en',
        ]);

        $output = $commandTester->getDisplay();

        $this->assertContains('Passphrase has been updated', $output);

        $setup = self::$kernel->getContainer()->get(Setup::class);
        $this->assertEquals('changex', $setup->getPassphrase());
    }

    public function testExecuteWithoutInteraction()
    {
        $command = $this->console->find('setup:passphrase:change');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'command' => $command->getName(),
            '--locale' => 'en',
            '--passphrase' => 'welcome',
            '--new-passphrase' => 'changex',
            '--install-version' => '0.1.0',
            '--no-interaction' => null,
        ]);

        $output = $commandTester->getDisplay();

        $this->assertContains('Passphrase has been updated', $output);

        $setup = self::$kernel->getContainer()->get(Setup::class);
        $this->assertEquals('changex', $setup->getPassphrase());
    }
}
