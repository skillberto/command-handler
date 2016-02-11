<?php

namespace Skillberto\CommandHandler\Tests;

use Skillberto\CommandHandler\Command;

class CommandTest extends \PHPUnit_Framework_TestCase
{
    public function testCommand()
    {
        $command = new Command('asd');

        $this->assertEquals('asd', $command->get());
        $this->assertFalse($command->isSkippable());
    }

    public function testSkippableCommand()
    {
        $command = new Command('asd', true);

        $this->assertEquals('asd', $command->get());
        $this->assertTrue($command->isSkippable());
    }

    public function testTimeout()
    {
        $command = new Command('asd', false, 0.1);

        $this->assertEquals(0.1, $command->getTimeout());
        $this->assertEquals('asd', $command->get());
    }
}