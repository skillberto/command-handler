<?php

namespace Skillberto\CommandHandler;

class Command
{
    protected $command = "";

    protected $timeout = null;

    protected $skip = false;

    public function add($command)
    {
        $this->command = $command;

        return $this;
    }

    public function skippable($skip = true)
    {
        $this->skip = $skip;

        return $this;
    }

    public function isSkippable()
    {
        return $this->skip;
    }

    public function get()
    {
        return $this->command;
    }

    public function setTimeout($timeout = null)
    {
        $this->timeout = $timeout;

        return $this;
    }

    public function getTimeout()
    {
        return $this->timeout;
    }
}