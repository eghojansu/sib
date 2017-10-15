<?php

namespace Eghojansu\Bundle\SetupBundle\Tests\Command;

use TestHelper;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SetupVersionsCommandTest extends KernelTestCase
{
    protected function setUp()
    {
        TestHelper::prepare();
    }

    public function testExecute()
    {
        static::bootKernel();

        $application = new Application(self::$kernel);

        $command = $application->find('setup:versions');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'command' => $command->getName(),
        ]);
        $output = $commandTester->getDisplay();

        $this->assertContains('0.1.0', $output);
        $this->assertContains('0.2.0', $output);
    }
}
