<?php

namespace Skillberto\CommandHandler\Tests;

use Skillberto\CommandHandler\Command;

class CommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Command
     */
    protected $command;

    protected function setUp()
    {
        $this->command = new Command();
        $this->command->set('asd');
    }

    public function testCommand()
    {
        $this->assertEquals('asd', $this->command->get());
        $this->assertFalse($this->command->isSkippable());
    }

    public function testSkippableCommand()
    {
        $this->command->skippable();

        $this->assertEquals('asd', $this->command->get());
        $this->assertTrue($this->command->isSkippable());

        $this->command->skippable(false);

        $this->assertEquals('asd', $this->command->get());
        $this->assertFalse($this->command->isSkippable());
    }

    public function testTimeout()
    {
        $this->command->setTimeout(0.1);

        $this->assertEquals(0.1, $this->command->getTimeout());
        $this->assertEquals('asd', $this->command->get());
    }
}