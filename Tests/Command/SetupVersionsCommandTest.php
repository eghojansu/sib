<?php

namespace Eghojansu\Bundle\SetupBundle\Tests\Command;

use TestHelper;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SetupVersionsCommandTest extends KernelTestCase
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
        $command = $this->console->find('setup:versions');
        $commandTester = new CommandTester($command);

        $commandTester->setInputs([
            'welcome',
        ]);

        $commandTester->execute([
            'command' => $command->getName(),
            '--locale' => 'en',
        ]);
        $output = $commandTester->getDisplay();

        $this->assertContains('0.1.0', $output);
        $this->assertContains('0.2.0', $output);
    }

    public function testExecuteWithoutInteraction()
    {
        $command = $this->console->find('setup:versions');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'command' => $command->getName(),
            '--locale' => 'en',
            '--passphrase' => 'welcome',
            '--no-interaction' => null,
            '--show-all' => null,
        ]);
        $output = $commandTester->getDisplay();

        $this->assertNotContains('0.1.0', $output);
        $this->assertNotContains('0.2.0', $output);
    }
}
