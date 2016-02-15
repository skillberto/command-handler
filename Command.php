<?php

namespace Skillberto\CommandHandler;

class Command
{
    protected $command = '';

    protected $timeout = null;

    protected $required = true;

    /**
     * @param string         $command
     * @param bool           $required
     * @param int|float|null $timeout  In seconds
     */
    public function __construct($command, $required = true, $timeout = null)
    {
        $this->setCommand($command);
        $this->setRequired($required);
        $this->setTimeout($timeout);
    }

    /**
     * @param string $command
     *
     * @return $this
     */
    public function setCommand($command)
    {
        $this->command = $command;

        return $this;
    }

    /**
     * @param bool $required
     *
     * @return $this
     */
    public function setRequired($required = true)
    {
        $this->required = $required;

        return $this;
    }

    /**
     * @param int|float|null $timeout
     *
     * @return $this
     */
    public function setTimeout($timeout = null)
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * @return bool
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * @return int|float|null
     */
    public function getTimeout()
    {
        return $this->timeout;
    }
}
