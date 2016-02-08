<?php

namespace Skillberto\CommandHandler\Tests;

use Skillberto\CommandHandler\CommandHandler;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutput;

class CommandHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CommandHandler
     */
    protected $commandHandler;

    protected $correctCommand = "php ./Tests/test.php";

    protected $correctOutput = "Executing: php ./Tests/test.php\nfoo";

    protected $skipOutput = "Skip: php ./Tests/wrongTest.php\n";

    protected $errorOutput = "Error: php ./Tests/wrongTest.php\n";

    protected function setUp()
    {
        $output = new BufferedOutput();

        $this->commandHandler = new CommandHandler($output);
        $this->commandHandler
            ->addCommand($this->correctCommand)
            ->addCommands(array($this->correctCommand));
    }

    public function testWithoutSkip()
    {
        $that = $this;

        $this->commandHandler->execute(function($commandHandler) use ($that) {
            $that->assertEquals(
                $this->formatOutput($this->correctOutput),
                $this->formatOutput($commandHandler->getOutput()->fetch())
            );
        });
    }

    public function testWithoutSkipAsSkippable()
    {
        $this->commandHandler
            ->addSkippableCommand($this->correctCommand)
            ->addSkippableCommands(array($this->correctCommand))
            ->execute();

        $this->commandHandler->getSkippedMessages();

        $this->assertEquals(
            $this->formatOutput($this->correctOutput . $this->correctOutput . $this->correctOutput. $this->correctOutput),
            $this->formatOutput($this->commandHandler->getOutput()->fetch())
        );
    }

    public function testWithSkip()
    {
        //Todo: create not successfull process
    }

    protected function formatOutput($output)
    {
        return preg_replace( "/\r|\n/", "",$output);
    }
}