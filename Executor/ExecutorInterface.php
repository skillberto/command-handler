<?php
/**
 * Created by PhpStorm.
 * User: hnorbert
 * Date: 4/26/17
 * Time: 3:01 PM
 */

namespace Skillberto\CommandHandler\Executor;

use \Closure;
use Skillberto\CommandHandler\Command;

interface ExecutorInterface
{
    public function execute(Command $command, Closure $callback = null): bool;
}