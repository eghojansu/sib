<?php

namespace Eghojansu\Bundle\SetupBundle\Tests\Command;

use TestHelper;
use Eghojansu\Bundle\SetupBundle\Service\Setup;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SetupPassphraseChangeCommandTest extends KernelTestCase
{
    protected function setUp()
    {
        TestHelper::prepare();
    }

    public function testExecute()
    {
        static::bootKernel();

        $application = new Application(self::$kernel);

        $command = $application->find('setup:passphrase:change');
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
}
