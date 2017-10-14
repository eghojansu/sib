<?php

namespace Eghojansu\Bundle\SetupBundle\Service;

use Closure;
use RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\HttpKernel\KernelInterface;
use Eghojansu\Bundle\SetupBundle\Utils\ArrayHelper;
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
     * Get Application console
     * @return Symfony\Bundle\FrameworkBundle\Console\Application
     */
    public function getConsole()
    {
        return $this->console;
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
        // normalize array input parameter
        $parameters = ArrayHelper::create($arguments)
            ->flatten()
            ->swapNumericKeyWithValue()
            ->add('command', $this->normalizeCommand($name))
            ->getValue();
        $input = new ArrayInput($parameters);

        return $this->runCommand($input);
    }

    /**
     * Execute closure
     * @param  Closure $closure Closure should return true, otherwise throw exception
     * @return this
     *
     * @throws RuntimeException
     */
    public function execute(Closure $closure)
    {
        $result = call_user_func($closure);

        if (true !== $result) {
            throw new RuntimeException('Closure must return boolean true');
        }

        return $this;
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
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * Transform camelCase to colon/dash/underscore separated line
     *
     * @param  string $methodName
     * @return string
     */
    private function normalizeCommand($methodName)
    {
        $pattern = '/^(?<mode>colon|dash|_)?(?:[cC]ommand)?(?<command>\w+)$/';
        preg_match($pattern, $methodName, $match);

        $replacePrefix = ':';
        if ($match['mode'] === 'dash') {
            $replacePrefix = '-';
        } elseif ($match['mode'] === '_') {
            $replacePrefix = '_';
        }

        return preg_replace_callback('/(?<capital>[[:upper:]])/', function($match) use ($replacePrefix) {
            return $replacePrefix.strtolower($match['capital']);
        }, lcfirst($match['command']));
    }
}
