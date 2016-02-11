<?php

namespace Skillberto\CommandHandler;

class Command
{
    protected $command = "";

    protected $timeout = null;

    protected $skip = false;

    public function __construct($command, $skip = false, $timeout = null)
    {
        $this->command = $command;
        $this->skip    = $skip;
        $this->timeout = $timeout;
    }

    public function isSkippable()
    {
        return $this->skip;
    }

    public function get()
    {
        return $this->command;
    }

    public function getTimeout()
    {
        return $this->timeout;
    }
}