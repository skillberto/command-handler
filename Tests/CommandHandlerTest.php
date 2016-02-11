<?php

namespace Skillberto\CommandHandler\Tests;

use Skillberto\CommandHandler\Command;
use Skillberto\CommandHandler\CommandHandler;
use Symfony\Component\Process\Process;

class CommandHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CommandHandler
     */
    protected $commandHandler;

    protected $correctCommand = "php ./Tests/test.php";

    protected $correctCommand2 = "php ./Tests/test2.php";

    protected $correctOutput = "Executing: php ./Tests/test.php\nfoo";

    protected $skipOutput = "Skip: php ./Tests/wrongTest.php\n";

    protected $errorOutput = "Error: php ./Tests/wrongTest.php\n";

    protected function setUp()
    {
        $output = new BufferedOutput();

        $this->commandHandler = new CommandHandler($output);
        $this->commandHandler
            ->addCommandString($this->correctCommand)
            ->addCommandStrings(array($this->correctCommand));
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
            ->addSkippableCommandString($this->correctCommand)
            ->addSkippableCommandStrings(array($this->correctCommand))
            ->execute();

        $this->commandHandler->getSkippedMessages();

        $this->assertEquals(
            $this->formatOutput($this->correctOutput . $this->correctOutput . $this->correctOutput. $this->correctOutput),
            $this->formatOutput($this->commandHandler->getOutput()->fetch())
        );
    }

    public function testTimeoutAndCallableExecute()
    {
        $that = $this;

        $this->commandHandler
            ->addCommand(new Command($this->correctCommand2, false, 0.2))
            ->setTimeout(0.1)
            ->execute(function(Process $process, Command $command) use ($that) {
                if ($that->correctCommand2 == $command->get()) {
                    $that->assertEquals($process->getTimeout(), $command->getTimeout());
                } else {
                    $that->assertEquals($process->getTimeout(), $that->commandHandler->getTimeout());
                }
            });
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