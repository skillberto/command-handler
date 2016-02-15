<?php

namespace Skillberto\CommandHandler;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\PhpProcess;
use Symfony\Component\Process\Process;

class CommandHandler
{
    const MERGE_NON = 0;

    const MERGE_NOT_DEFINED = 1;

    const MERGE_ALL = 2;

    protected $commands = array();

    protected $skipped = array();

    protected $error = null;

    protected $output;

    protected $timeout = null;

    protected $prefix;

    /**
     * @param OutputInterface $outputInterface
     * @param string          $prefix
     * @param float           $timeout
     */
    public function __construct(OutputInterface $outputInterface, $prefix = "", $timeout = null)
    {
        $this->output  = $outputInterface;
        $this->prefix  = $prefix;
        $this->timeout = $timeout;
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

    public function addPrefix($prefix = "")
    {
        $this->prefix = $prefix;
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @return OutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @param  Command $command
     * @return $this
     */
    public function addCommand(Command $command)
    {
        $data = $this->prefix . $command->getCommand();

        $command->setCommand($data);

        if (null === $command->getTimeout()) {
            $command->setTimeout($this->getTimeout());
        }

        $this->commands[] = $command;

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
        $command = $this->createCommand($commandString, false);

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
     * Everything used from the injected handler for that commands, except the prefix and timeout. They are optional.
     *
     * If $mergePrefix or $mergeTimeout params are MERGE_ALL, then will used the current property.
     * If MERGE_NOT_DEFINED, then will used the current property if the injected not defined, otherwise use the injected property.
     *
     * @param  CommandHandler $handler  An other CommandHandler instance
     * @param  int            $prefix   MERGE_ALL | MERGE_NOT_DEFINED | MERGE_NON
     * @param  int            $timeout  MERGE_ALL | MERGE_NOT_DEFINED | MERGE_NON
     * @return $this
     */
    public function addHandler(CommandHandler $handler, $mergePrefix = self::MERGE_NON, $mergeTimeout = self::MERGE_NON)
    {
        foreach ($handler->getCommands() as $command) {
            $internalPrefix  = ($mergePrefix == self::MERGE_ALL || ($mergePrefix == self::MERGE_NOT_DEFINED && $handler->getPrefix() == "")) ? $this->getPrefix() : "";
            $internalTimeout = ($mergeTimeout == self::MERGE_ALL || ($mergeTimeout == self::MERGE_NOT_DEFINED && $command->getTimeout() === null)) ? $this->getTimeout() : $command->getTimeout();

            $data = $internalPrefix . $command->getCommand();

            $command->setCommand($data);
            $command->setTimeout($internalTimeout);

            $this->commands[] = $command;
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

    protected function iterateCommands(Command $command, $callback = null)
    {
        $that = $this;

        $this->info($command, 'Executing');

        $p = $this->createProcess($command->getCommand());
        $p->setTimeout($command->getTimeout());
        $p->setPty(true);
        $p->run(function($type, $data) use ($that, $callback, $p, $command) {
            $that->output->write($data, false, OutputInterface::OUTPUT_RAW);

            if ($callback !== null) {
                call_user_func_array($callback, array($p, $command));
            }
        });

        if (!$p->isSuccessful()){
            if ($command->isRequired()) {
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
        $this->output->writeln(sprintf('<info>%s:</info> %s', $info, $command->getCommand()));
    }

    /**
     * @param string $command
     * @param bool   $required
     * @param float  $timeout
     *
     * @return Command
     */
    protected function createCommand($command, $required = true, $timeout = null)
    {
        return new Command($command, $required, $timeout);
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