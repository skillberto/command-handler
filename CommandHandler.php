<?php

namespace Skillberto\CommandHandler;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\PhpProcess;
use Symfony\Component\Process\Process;

class CommandHandler
{
    protected $commands = array();

    protected $skipped = array();

    protected $error = null;

    protected $output;

    protected $timeout;

    protected $prefix;

    /**
     * @param OutputInterface $outputInterface
     * @param string          $prefix
     */
    public function __construct(OutputInterface $outputInterface, $prefix = "")
    {
        $this->output = $outputInterface;
        $this->prefix = $prefix;
    }

    /**
     * @param  float|null $timeout
     * @return $this
     */
    public function setTimeout($timeout = null)
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * @return float
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @param  Command $command
     * @return $this
     */
    public function addCommand(Command $command)
    {
        $newCommand = $command;

        if ($this->prefix) {
            $data = $this->prefix." ".$command->get();

            $newCommand = $this->createCommand($data, $command->isSkippable(), $command->getTimeout());
        }

        $this->commands[] = $newCommand;

        return $this;
    }

    /**
     * @param  array $commands
     * @return $this
     */
    public function addCommands(array $commands)
    {
        foreach ($commands as $command) {
            $this->addCommand($command);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getCommands()
    {
        return $this->commands;
    }

    /**
     * @param  string $commandString
     * @return $this
     */
    public function addCommandString($commandString)
    {
        $command = $this->createCommand($commandString);

        $this->addCommand($command);

        return $this;
    }

    /**
     * @param  array $commandStrings
     * @return $this
     */
    public function addCommandStrings(array $commandStrings)
    {
        foreach ($commandStrings as $commandString) {
            $this->addCommandString($commandString);
        }

        return $this;
    }

    /**
     * @param  string $commandString
     * @return $this
     */
    public function addSkippableCommandString($commandString)
    {
        $command = $this->createCommand($commandString, true);

        $this->addCommand($command);

        return $this;
    }

    /**
     * @param  array $commandStrings
     * @return $this
     */
    public function addSkippableCommandStrings(array $commandStrings)
    {
        foreach ($commandStrings as $commandString) {
            $this->addSkippableCommandString($commandString);
        }

        return $this;
    }

    /**
     * @param  \Closure $callback Current Process and Command are injected
     * @return $this
     */
    public function execute(\Closure $callback = null)
    {
        foreach ($this->commands as $command) {
            if (! $this->iterateCommands($command, $callback)) {
                return $this;
            }
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function hasSkipped()
    {
        return (count($this->skipped) > 0) ? true : false;
    }

    /**
     * @return bool
     */
    public function hasError()
    {
        return $this->error ? true : false;
    }

    public function getSkippedMessages()
    {
        if (! $this->hasSkipped()) {
            return;
        }

        foreach ($this->skipped as $command) {
            $this->info($command, 'Skipped');
        }
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        if (! $this->hasError()) {
            return;
        }

        $this->info($this->error, 'Error');
    }

    /**
     * @return OutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }

    protected function iterateCommands(Command $command, \Closure $callback = null)
    {
        $that = $this;

        $this->info($command, 'Executing');

        $p = $this->createProcess($command->get());
        $p->setTimeout($command->getTimeout() ?: $this->getTimeout());
        $p->run(function($type, $data) use ($that, $callback, $p, $command) {
            $that->output->write($data, false, OutputInterface::OUTPUT_RAW);

            if ($callback) {
                call_user_func_array($callback, array($p, $command));
            }
        });

        if (!$p->isSuccessful()){
            if ($command->isSkippable() === false) {
                $this->error = $command;

                return false;
            } else {
                $this->skipped[] = $command;
            }
        }

        $this->output->writeln("");

        return true;
    }

    /**
     * @param Command $command
     * @param string  $info
     */
    protected function info($command, $info)
    {
        $this->output->writeln(sprintf('<info>%s:</info> %s', $info, $command->get()));
    }

    /**
     * @return Command
     */
    protected function createCommand($command, $skip = false, $timeout = null)
    {
        return new Command($command, $skip, $timeout);
    }

    /**
     * @param  string $commandString
     *
     * @return PhpProcess
     */
    protected function createProcess($commandString)
    {
        return new Process($commandString, null, null, fopen('php://stdin', 'r'));
    }
}