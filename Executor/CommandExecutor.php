<?php
/**
 * Created by PhpStorm.
 * User: hnorbert
 * Date: 4/26/17
 * Time: 3:00 PM
 */

namespace Skillberto\CommandHandler\Executor;

use Skillberto\CommandHandler\Command;
use Skillberto\CommandHandler\CommandCollection;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class CommandExecutor implements ExecutorInterface
{
    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var CommandCollection
     */
    protected $skippedCommandCollection;

    /**
     * CommandExecutor constructor.
     *
     * @param OutputInterface   $output
     * @param CommandCollection $commandCollection
     */
    public function __construct(OutputInterface $output, CommandCollection $skippedCommandCollection)
    {
        $this->output = $output;
        $this->skippedCommandCollection = $skippedCommandCollection;
    }

    /**
     * @param \Closure|null $callback
     *
     * @return bool
     */
    public function execute(Command $command, \Closure $callback = null): bool
    {
        $that = $this;

        $p = $this->createProcess($command->getCommand());
        $p->setTimeout($command->getTimeout());
        $p->setPty(true);
        $p->run(function ($type, $data) use ($that, $callback, $p, $command) {
            $that->output->write($data, false, OutputInterface::OUTPUT_RAW);

            if ($callback !== null) {
                call_user_func_array($callback, array($p, $command));
            }
        });

        if (! $p->isSuccessful()) {
            if ($command->isRequired()) {
                return false;
            } else {
                $that->skippedCommandCollection->add($command);
            }
        }

        return true;
    }

    /**
     * @param  string $commandString
     *
     * @return Process
     */
    protected function createProcess(string  $commandString): Process
    {
        return new Process($commandString, null, null, fopen('php://stdin', 'r'));
    }
}