<?php
/**
 * Created by PhpStorm.
 * User: hnorbert
 * Date: 4/26/17
 * Time: 3:42 PM
 */

namespace Skillberto\CommandHandler\Factory;

use Skillberto\CommandHandler\CommandCollection;
use Skillberto\CommandHandler\Executor\CommandExecutor;
use Symfony\Component\Console\Output\OutputInterface;

class CommandExecutorFactory
{
    protected $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function create(CommandCollection $skippedCommandCollection)
    {
        return new CommandExecutor($this->output, $skippedCommandCollection);
    }
}