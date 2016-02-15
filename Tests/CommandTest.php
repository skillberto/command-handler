<?php

namespace Skillberto\CommandHandler\Tests;

use Skillberto\CommandHandler\Command;

class CommandTest extends \PHPUnit_Framework_TestCase
{
    public function testCommand()
    {
        $command = new Command('asd');

        $this->assertNull($command->getTimeout());
        $this->assertTrue($command->isRequired());
        $this->assertEquals('asd', $command->getCommand());
    }

    public function testNotRequiredCommand()
    {
        $command = new Command('asd', false);

        $this->assertNull($command->getTimeout());
        $this->assertFalse($command->isRequired());
        $this->assertEquals('asd', $command->getCommand());
    }

    public function testTimeout()
    {
        $command = new Command('asd', true, 0.1);

        $this->assertEquals(0.1, $command->getTimeout());
        $this->assertTrue($command->isRequired());
        $this->assertEquals('asd', $command->getCommand());
    }
}
