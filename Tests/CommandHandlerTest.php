<?php

namespace Skillberto\CommandHandler\Tests;

use Skillberto\CommandHandler\Command;
use Skillberto\CommandHandler\CommandHandler;
use Symfony\Component\Process\Process;

class CommandHandlerTest extends \PHPUnit_Framework_TestCase
{
    protected $prefix = "php ";

    /**
     * @var CommandHandler
     */
    protected $commandHandler;

    protected $correctCommand_1 = "php ./Tests/Resources/test_correct_1.php";

    protected $correctCommand_2 = "php ./Tests/Resources/test_correct_2.php";

    protected $wrongCommand = "php ./Tests/Resources/test_wrong.php";

    protected $correctOutput = "Executing: php ./Tests/Resources/test_correct_1.php\nfoo";

    protected $wrongOutput = "Executing: php ./Tests/Resources/test_wrong.php\n";

    protected $skipOutput = "Skipped: php ./Tests/Resources/test_wrong.php\n";

    protected $errorOutput = "Error: php ./Tests/Resources/test_wrong.php\n";

    protected function setUp()
    {
        $this->commandHandler = $this->createHandler($this->correctCommand_1);
    }

    public function testCorrectCommandWithoutSkip()
    {
        $this->commandHandler->execute();

        $this->assertOutputEquals(
            $this->correctOutput . $this->correctOutput
        );
    }

    public function testCorrectCommandWithSkip()
    {
        $this->commandHandler
            ->addSkippable($this->correctCommand_1)
            ->addSkippableCollection(array($this->correctCommand_1))
            ->execute();

        $this->assertOutputEquals(
            $this->correctOutput . $this->correctOutput . $this->correctOutput . $this->correctOutput
        );
    }

    public function testCorrectCommandWithTimeout()
    {
        $that = $this;

        $this->commandHandler
            ->addCommand(new Command($this->correctCommand_2, false, 0.3))
            ->setTimeout(0.2)
            ->execute(function(Process $process, Command $command) use ($that) {
                if ($that->correctCommand_2 == $command->get()) {
                    $that->assertEquals($process->getTimeout(), $command->getTimeout());
                } else {
                    $that->assertEquals($process->getTimeout(), $that->commandHandler->getTimeout());
                }
            });
    }

    public function testWrongCommandWithSkip()
    {
        $this->commandHandler
            ->addSkippable($this->wrongCommand)
            ->execute();

        $this->assertOutputEquals(
            $this->correctOutput . $this->correctOutput . $this->wrongOutput . $this->skipOutput
        );
    }

    public function testWrongCommandWithoutSkipAsError()
    {
        $this->commandHandler
            ->add($this->wrongCommand)
            ->execute();

        $this->assertOutputEquals(
            $this->correctOutput . $this->correctOutput . $this->wrongOutput . $this->errorOutput
        );

    }

    public function testPrefix()
    {
        $command = $this->prepareCommandWithoutPrefix($this->correctCommand_1, $this->prefix);

        $this->commandHandler = $this->createHandler($command, $this->prefix);

        $this->commandHandler->execute();

        $this->assertOutputEquals(
            $this->correctOutput . $this->correctOutput
        );
    }

    public function testAddHandler()
    {
        $handler = $this->createHandler($this->correctCommand_1);

        $this->commandHandler
            ->addHandler($handler)
            ->execute();

        $this->assertOutputEquals(
            $this->correctOutput . $this->correctOutput . $this->correctOutput . $this->correctOutput
        );
    }

    public function testAddHandlerWithLocalPrefix()
    {
        //inject with prefix and not use the local
        $handler = $this->createHandler($this->correctCommand_1);

        $command = $this->prepareCommandWithoutPrefix($this->correctCommand_1, $this->prefix);

        $this->commandHandler = $this->createHandler($command, $this->prefix);
        $this->commandHandler
            ->addHandler($handler)
            ->execute();

        $this->assertOutputEquals(
            $this->correctOutput . $this->correctOutput . $this->correctOutput . $this->correctOutput
        );

        //inject without prefix and use the local
        $command = $this->prepareCommandWithoutPrefix($this->correctCommand_1, $this->prefix);

        $handler = $this->createHandler($command);

        $this->commandHandler
            ->addHandler($handler, true)
            ->execute();

        $this->assertOutputEquals(
            $this->correctOutput . $this->correctOutput . $this->correctOutput . $this->correctOutput . $this->correctOutput . $this->correctOutput
        );
    }

    public function testAddHandlerOnlyWithLocalTimeout()
    {
        $that = $this;

        $handler = $that->createHandler($that->correctCommand_2);

        //not use local
        $that->commandHandler
            ->setTimeout(0.2)
            ->addHandler($handler)
            ->execute(function(Process $process, Command $command) use ($that){
                if ($that->correctCommand_2 == $command->get()) {
                    $that->assertNull($process->getTimeout());
                } else {
                    $that->assertEquals($process->getTimeout(), $that->commandHandler->getTimeout());
                }
            });

        //use local
        $that->commandHandler = $that->createHandler($that->correctCommand_1);
        $that->commandHandler
            ->setTimeout(0.2)
            ->addHandler($handler, false, true)
            ->execute(function(Process $process, Command $command) use ($that){
                $that->assertEquals($process->getTimeout(), $that->commandHandler->getTimeout());
            });
    }

    /**
     * @param  $output
     * @return string
     */
    protected function formatOutput($output)
    {
        return preg_replace( "/\r|\n/", "",$output);
    }

    /**
     * Format expected parameter(as expected) and output of commandHandler (as actual) with formatOutput, and compare them with assertEquals (the expected and actual).
     *
     * @param $expected
     */
    protected function assertOutputEquals($expected)
    {
        $this->commandHandler->getSkippedMessages();
        $this->commandHandler->getErrorMessage();

        $this->assertEquals(
            $this->formatOutput($expected),
            $this->formatOutput($this->commandHandler->getOutput()->fetch())
        );
    }

    /**
     * Cut prefix from the beginning of command and return that
     *
     * @param  string $command
     * @param  string $prefix
     * @return string
     */
    protected function prepareCommandWithoutPrefix($command, $prefix)
    {
        if (substr($command, 0, strlen($prefix)) == $prefix) {
            $command = substr($command, strlen($prefix));
        } else {
            throw new \InvalidArgumentException(sprintf('%s command is incorrect, %s prefix is not found at the beginning of command.', $command, $prefix));
        }

        return $command;
    }

    /**
     * @param  string $command
     * @param  string $prefix
     * @return CommandHandler With two command ($command)
     */
    protected function createHandler($command, $prefix = "")
    {
        $output = new BufferedOutput();

        $commandHandler = new CommandHandler($output, $prefix);
        $commandHandler
            ->add($command)
            ->addCollection(array($command));

        return $commandHandler;
    }
}