<?php

namespace Eghojansu\Bundle\SetupBundle\Service;

use RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;

class CommandHelper
{
    /** @var Symfony\Bundle\FrameworkBundle\Console\Application */
    private $console;

    /** @var Symfony\Component\Console\Output\OutputInterface */
    private $output;


    public function __construct(KernelInterface $kernel)
    {
        $this->console = new Application($kernel);
        $this->console->setAutoExit(false);
    }

    /**
     * This class will perform console command
     * pass an array pair of argument/command name and its value
     *
     * @param  string $name
     * @param  array  $arguments
     * @return this
     */
    public function __call($name, array $arguments)
    {
        $input = $this->createInput($name, array_shift($arguments));

        return $this->runCommand($input);
    }

    /**
     * Run command from input
     *
     * @param  Symfony\Component\Console\Input\InputInterface $input
     * @return this
     */
    public function runCommand(InputInterface $input)
    {
        $output = new BufferedOutput();
        $result = $this->console->run($input, $output);

        if (0 !== $result) {
            $message = 'Command error w/m ' . $output->fetch();

            throw new RuntimeException($message);
        }

        $this->output = $output;

        return $this;
    }

    /**
     * Get last output
     *
     * @return Symfony\Component\Console\Output\OutputInterface
     */
    public function getLastOutput()
    {
        return $this->output;
    }

    /**
     * Create input
     *
     * @param  string $commandName
     * @param  mixed $setup
     * @return Symfony\Component\Console\Input\ArrayInput
     */
    private function createInput($commandName, $setup = null)
    {
        $parameters = (array) $setup;
        $parameters['command'] = $this->normalizeCommand($commandName);

        return new ArrayInput($parameters);
    }

    /**
     * Transform camelCase to colon separated line
     *
     * @param  string $commandName
     * @return string
     */
    private function normalizeCommand($commandName)
    {
        return strtolower(preg_replace('/([A-Z])/', ':$1', $commandName));
    }
}
