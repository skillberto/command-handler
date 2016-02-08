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

    /**
     * @param OutputInterface $outputInterface
     */
    public function __construct(OutputInterface $outputInterface)
    {
        $this->output = $outputInterface;
    }

    public function setTimeout($timeout = null)
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * @param $commandString
     * @return $this
     */
    public function addCommand($commandString)
    {
        $command = $this->createCommand()->add($commandString);

        $this->commands[] = $command;

        return $this;
    }

    /**
     * @param  array $commandStrings
     * @return $this
     */
    public function addCommands(array $commandStrings)
    {
        foreach ($commandStrings as $commandString) {
            $this->addCommand($commandString);
        }

        return $this;
    }

    /**
     * @param  string $commandString
     * @return $this
     */
    public function addSkippableCommand($commandString)
    {
        $command = $this->createCommand()->add($commandString)->skippable();

        $this->commands[] = $command;

        return $this;
    }

    /**
     * @param  array $commandStrings
     * @return $this
     */
    public function addSkippableCommands(array $commandStrings)
    {
        foreach ($commandStrings as $commandString) {
            $this->addSkippableCommand($commandString);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function execute($callback = null)
    {
        $that = $this;

        foreach ($this->commands as $command) {

            $this->info($command, 'Executing');

            $p = $this->createProcess($command->get());
            $p->setTimeout($this->timeout ?: $command->getTimeout());
            $p->run(function($type, $data) use ($that, $callback) {
                $that->output->write($data, false, OutputInterface::OUTPUT_RAW);

                if ($callback) {
                    call_user_func($callback, $that);
                }
            });

            if (!$p->isSuccessful()){
                if ($command->isSkippable() === false) {
                    $this->error = $command;

                    return $this;
                } else {
                    $this->skipped[] = $command;
                }
            }

            $this->output->writeln("");
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

    public function getOutput()
    {
        return $this->output;
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
    protected function createCommand()
    {
        return new Command();
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