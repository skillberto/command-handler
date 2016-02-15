<?php

namespace Skillberto\CommandHandler;

class Command
{
    protected $command = "";

    protected $timeout = null;

    protected $required = true;

    public function __construct($command, $required = true, $timeout = null)
    {
        $this->setCommand($command);
        $this->setRequired($required);
        $this->setTimeout($timeout);
    }

    public function setCommand($command)
    {
        $this->command = $command;
    }

    public function setRequired($required = true)
    {
        $this->required = $required;
    }

    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    public function isRequired()
    {
        return $this->required;
    }

    public function getCommand()
    {
        return $this->command;
    }

    public function getTimeout()
    {
        return $this->timeout;
    }
}