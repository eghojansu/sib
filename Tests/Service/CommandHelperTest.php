<?php

namespace Eghojansu\Bundle\SetupBundle\Tests\Service;

use RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Eghojansu\Bundle\SetupBundle\Service\CommandHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CommandHelperTest extends KernelTestCase
{
    /** @var Eghojansu\Bundle\SetupBundle\Service\CommandHelper */
    private $command;

    protected function setUp()
    {
        self::bootKernel();
        $this->command = self::$kernel->getContainer()->get(CommandHelper::class);
        $console = $this->command->getConsole();
        $console->register('single')
            ->addOption('bar', null, InputOption::VALUE_REQUIRED)
            ->addOption('baz', null, InputOption::VALUE_NONE)
            ->setCode(function(InputInterface $input, OutputInterface $output) {
                $bar = $input->getOption('bar');
                $line = "single command";

                if ($bar) {
                    $line .= " with option bar=$bar";
                }
                if ($input->getOption('baz')) {
                    $line .= " with option baz";
                }

                $output->write($line);
            });
        $console->register('cmd:test:colon')
            ->addOption('bar', null, InputOption::VALUE_REQUIRED)
            ->addOption('baz', null, InputOption::VALUE_NONE)
            ->setCode(function(InputInterface $input, OutputInterface $output) {
                $bar = $input->getOption('bar');
                $line = "cmd:test:colon command";

                if ($bar) {
                    $line .= " with option bar=$bar";
                }
                if ($input->getOption('baz')) {
                    $line .= " with option baz";
                }

                $output->write($line);
            });
        $console->register('cmd-test-dash')
            ->addOption('bar', null, InputOption::VALUE_REQUIRED)
            ->addOption('baz', null, InputOption::VALUE_NONE)
            ->setCode(function(InputInterface $input, OutputInterface $output) {
                $bar = $input->getOption('bar');
                $line = "cmd-test-dash command";

                if ($bar) {
                    $line .= " with option bar=$bar";
                }
                if ($input->getOption('baz')) {
                    $line .= " with option baz";
                }

                $output->write($line);
            });
        $console->register('cmd_test_underscore')
            ->addOption('bar', null, InputOption::VALUE_REQUIRED)
            ->addOption('baz', null, InputOption::VALUE_NONE)
            ->setCode(function(InputInterface $input, OutputInterface $output) {
                $bar = $input->getOption('bar');
                $line = "cmd_test_underscore command";

                if ($bar) {
                    $line .= " with option bar=$bar";
                }
                if ($input->getOption('baz')) {
                    $line .= " with option baz";
                }

                $output->write($line);
            });
    }

    /**
     * @expectedException RuntimeException
     */
    public function testExecute()
    {
        $command = $this->command->execute(function() {
            return true;
        });

        $this->assertEquals($this->command, $command);

        $this->command->execute(function() {
            return false;
        });
    }

    /**
     * @expectedException RuntimeException
     */
    public function testRunCommand()
    {
        $input = new ArrayInput([
            'command' => 'single',
        ]);
        $output = $this->command->runCommand($input)->getOutput();
        $this->assertEquals('single command', $output->fetch());

        $input = new ArrayInput([
            'command' => 'single',
            '--bar' => 'baz',
            '--baz' => null,
        ]);
        $output = $this->command->runCommand($input)->getOutput();
        $this->assertEquals(
            'single command with option bar=baz with option baz',
            $output->fetch()
        );

        $input = new ArrayInput([
            'command' => 'command-that-not-exists',
        ]);
        $this->command->runCommand($input);
    }

    /**
     * @dataProvider proxyCommandProvider
     * @param  string $command
     * @param  array $args
     * @param  string $expectedOutput
     */
    public function testProxyCommand($command, $args, $expectedOutput)
    {
        $args = (array) $args;
        call_user_func_array([$this->command, $command], $args);

        $this->assertEquals(
            $expectedOutput,
            $this->command->getOutput()->fetch()
        );
    }

    public function proxyCommandProvider()
    {
        return [
            ['single', null, 'single command'],
            ['single', [['--bar'=>'baz']], 'single command with option bar=baz'],
            ['single', [['--bar'=>'baz', '--baz']], 'single command with option bar=baz with option baz'],
            ['single', ['--baz'], 'single command with option baz'],
            ['commandSingle', ['--baz'], 'single command with option baz'],

            ['cmdTestColon', [['--bar'=>'baz', '--baz']], 'cmd:test:colon command with option bar=baz with option baz'],
            ['commandCmdTestColon', [['--bar'=>'baz', '--baz']], 'cmd:test:colon command with option bar=baz with option baz'],
            ['colonCommandCmdTestColon', [['--bar'=>'baz', '--baz']], 'cmd:test:colon command with option bar=baz with option baz'],

            ['dashCommandCmdTestDash', [['--bar'=>'baz', '--baz']], 'cmd-test-dash command with option bar=baz with option baz'],

            ['_commandCmdTestUnderscore', [['--bar'=>'baz', '--baz']], 'cmd_test_underscore command with option bar=baz with option baz'],
        ];
    }
}
