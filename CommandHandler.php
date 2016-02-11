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

    protected $timeout = null;

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
        $data = $this->prefix . $command->get();

        $newCommand = $this->createCommand($data, $command->isSkippable(), $command->getTimeout());

        $this->commands[] = $newCommand;

        return $this;
    }

    /**
     * @param  array $commands Command collection
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
     * @return array Command collection
     */
    public function getCommands()
    {
        return $this->commands;
    }

    /**
     * @param  string $commandString
     * @return $this
     */
    public function add($commandString)
    {
        $command = $this->createCommand($commandString);

        $this->addCommand($command);

        return $this;
    }

    /**
     * @param  array $commandStrings
     * @return $this
     */
    public function addCollection(array $commandStrings)
    {
        foreach ($commandStrings as $commandString) {
            $this->add($commandString);
        }

        return $this;
    }

    /**
     * @param  string $commandString
     * @return $this
     */
    public function addSkippable($commandString)
    {
        $command = $this->createCommand($commandString, true);

        $this->addCommand($command);

        return $this;
    }

    /**
     * @param  array $commandStrings
     * @return $this
     */
    public function addSkippableCollection(array $commandStrings)
    {
        foreach ($commandStrings as $commandString) {
            $this->addSkippable($commandString);
        }

        return $this;
    }

    /**
     * Merge an other CommandHandler with this.
     * Everything used from the injected handler for that commands.
     *
     * If prefix does not exist in the injected handler, then can be used optionally the current.
     *
     * If timeout exists in the command, then use them. If not, but exists in the injected handler, then use them.
     * Otherwise, if the timeout input is true, then use the current timeout, if false, then will be "0.0".
     * In this case, the timeout of the current handler is not relevant
     *
     * @param  CommandHandler $handler  An other CommandHandler instance
     * @param  bool           $prefix   If true, then use the current prefix if the other doesn't exist, otherwise not.
     * @param  bool           $timeout  If true, then use the current timeout if the other doesn't exist, otherwise use "0.0".
     * @return $this
     */
    public function addHandler(CommandHandler $handler, $prefix = false, $timeout = false)
    {
        foreach ($handler->getCommands() as $command) {
            $internalPrefix  = ($handler->getPrefix() == "" && $prefix == true) ? $this->getPrefix() : $handler->getPrefix();
            $internalTimeout = (float) ($command->getTimeout() ?: ($handler->getTimeout() ?: (($handler->getTimeout() === null && $timeout == true) ? $this->getTimeout() : 0.0)));

            $data = $internalPrefix . $command->get();

            $newCommand = $this->createCommand($data, $command->isSkippable(), $internalTimeout);

            $this->commands[] = $newCommand;
        }

        return $this;
    }

    /**
     * @param  callback|null $callback Current Process and Command are injected
     * @return $this
     */
    public function execute($callback = null)
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

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    protected function iterateCommands(Command $command, $callback = null)
    {
        $that = $this;

        $this->info($command, 'Executing');

        $p = $this->createProcess($command->get());
        $p->setTimeout($command->getTimeout() !== null ? $command->getTimeout() : $this->getTimeout());
        $p->setPty(true);
        $p->run(function($type, $data) use ($that, $callback, $p, $command) {
            $that->output->write($data, false, OutputInterface::OUTPUT_RAW);

            if ($callback !== null) {
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