<?php
/**
 * Created by PhpStorm.
 * User: hnorbert
 * Date: 4/26/17
 * Time: 3:24 PM
 */

namespace Skillberto\CommandHandler;

use \ArrayIterator;
use \Countable;
use \IteratorAggregate;

class CommandCollection implements IteratorAggregate, Countable
{
    /**
     * @var Command[]
     */
    protected $commands;

    /**
     * @var int
     */
    protected $count = 0;

    /**
     * @var string
     */
    protected $type = '';

    /**
     * CommandCollection constructor.
     *
     * @param string $type
     */
    public function __construct(string $type = '')
    {
        $this->type = $type;

        $this->reset();
    }

    /**
     * @param Command $command
     *
     * @return CommandCollection
     */
    public function add(Command $command): CommandCollection
    {
        $this->commands[$this->count++] = $command;

        return $this;
    }

    /**
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->commands);
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->count;
    }

    /**
     *
     */
    public function reset(): void
    {
        $this->commands = [];
    }
}