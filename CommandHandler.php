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

    protected $php;

    /**
     * @param OutputInterface $outputInterface
     */
    public function __construct(OutputInterface $outputInterface, $php = true)
    {
        $this->output = $outputInterface;

        $this->php = $php;
    }

    /**
     * @param $commandString
     * @return $this
     */
    public function add($commandString)
    {
        $command = $this->createCommand()->add($commandString);

        $this->commands[] = $command;

        return $this;
    }

    /**
     * @param  string $commandString
     * @return $this
     */
    public function addSkippable($commandString)
    {
        $command = $this->createCommand()->add($commandString)->skippable();

        $this->commands[] = $command;

        return $this;
    }

    /**
     * @return $this
     */
    public function execute()
    {
        $output = $this->output;

        foreach ($this->commands as $command) {

            $this->info($command, 'Executing');

            $p = $this->createProcess($command->get());
            $p->setTimeout(null);
            $p->run(function($type, $data) use ($output) {
                $output->write($data, false, OutputInterface::OUTPUT_RAW);
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
        foreach ($this->skipped as $command) {
            $this->info($command, 'Skipped');
        }
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        $this->info($this->error, 'Error');
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
        if ($this->php) {
            return new PhpProcess($commandString);
        } else {
            return new Process($commandString);
        }
    }
}