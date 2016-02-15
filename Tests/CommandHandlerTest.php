<?php

namespace Skillberto\CommandHandler\Tests;

use Skillberto\CommandHandler\Command;
use Skillberto\CommandHandler\CommandHandler;
use Symfony\Component\Process\Process;

class CommandHandlerTest extends \PHPUnit_Framework_TestCase
{
    protected $prefix = "php ";

    protected $commandHandlerTimeout = 0.2;

    protected $injectedHandlerTimeout = 0.1;

    /**
     * @var CommandHandler
     */
    protected $commandHandler;

    protected $correctCommand_1 = "php ./Tests/Resources/test_correct_1.php";

    protected $correctCommand_2 = "php ./Tests/Resources/test_correct_2.php";

    protected $wrongCommand = "php ./Tests/Resources/test_wrong.php";

    protected $correctOutput_1 = "Executing: php ./Tests/Resources/test_correct_1.php\nfoo";

    protected $correctOutput_2 = "Executing: php ./Tests/Resources/test_correct_2.php\nfoo";

    protected $wrongOutput = "Executing: php ./Tests/Resources/test_wrong.php\n";

    protected $doublePrefixErrorOutput = "Executing: php php ./Tests/Resources/test_correct_2.php\nCould not open input file: php\nError: php php ./Tests/Resources/test_correct_2.php\n";

    protected $skipOutput = "Skipped: php ./Tests/Resources/test_wrong.php\n";

    protected $errorOutput = "Error: php ./Tests/Resources/test_wrong.php\n";

    protected function setUp()
    {
        $this->commandHandler = $this->createHandler($this->correctCommand_1);
    }

    public function testCorrectCommand()
    {
        $that = $this;

        $this->commandHandler->execute(function(Process $process, Command $command) use ($that) {
            $that->assertNull($process->getTimeout());
        });

        $this->assertOutputEquals(
            $this->correctOutput_1 . $this->correctOutput_1
        );
    }

    public function testCorrectCommandWithSkip()
    {
        $this->commandHandler
            ->addSkippable($this->correctCommand_1)
            ->addSkippableCollection(array($this->correctCommand_1))
            ->execute();

        $this->assertOutputEquals(
            $this->correctOutput_1 . $this->correctOutput_1 . $this->correctOutput_1 . $this->correctOutput_1
        );
    }

    public function testCorrectCommandWithTimeout()
    {
        $that = $this;

        $that->commandHandler = $that->createHandler();

        $that->commandHandler
            ->setTimeout($that->commandHandlerTimeout)
            ->addCommand(new Command($that->correctCommand_2, true, $that->injectedHandlerTimeout))
            ->execute(function(Process $process, Command $command) use ($that) {
                if ($that->correctCommand_2 == $command->getCommand()) {
                    $that->assertEquals($that->injectedHandlerTimeout, $process->getTimeout());
                } else {
                    $that->assertEquals($that->commandHandlerTimeout, $process->getTimeout());
                }
            });
    }

    public function testWrongCommandWithSkip()
    {
        $this->commandHandler
            ->addSkippable($this->wrongCommand)
            ->execute();

        $this->assertOutputEquals(
            $this->correctOutput_1 . $this->correctOutput_1 . $this->wrongOutput . $this->skipOutput
        );
    }

    public function testWrongCommandWithoutSkipAsError()
    {
        $this->commandHandler
            ->add($this->wrongCommand)
            ->execute();

        $this->assertOutputEquals(
            $this->correctOutput_1 . $this->correctOutput_1 . $this->wrongOutput . $this->errorOutput
        );

    }

    public function testPrefix()
    {
        $command = $this->prepareCommandWithoutPrefix($this->correctCommand_1, $this->prefix);

        $this->commandHandler = $this->createHandler($command, $this->prefix);

        $this->commandHandler->execute();

        $this->assertOutputEquals(
            $this->correctOutput_1 . $this->correctOutput_1
        );
    }

    public function testAddHandlerWithoutLocalPropertiesWith_MERGE_ALL()
    {
        $that = $this;

        $this->createWithoutLocalProperties(
            CommandHandler::MERGE_ALL,
            function (Process $process, Command $command) use ($that) {
                $that->assertNull($process->getTimeout());
            },
            $that->correctOutput_1 . $that->correctOutput_1 . $that->correctOutput_2 . $that->correctOutput_2,
            function (Process $process, Command $command) use ($that) {
                $that->assertNull($process->getTimeout());
            },
            $that->correctOutput_1 . $that->correctOutput_1 . $that->correctOutput_2 . $that->correctOutput_2
        );
    }

    public function testAddHandlerWithLocalPropertiesWith_MERGE_ALL()
    {
        $that = $this;

        $this->createWithLocalProperties(
            CommandHandler::MERGE_ALL,
            function (Process $process, Command $command) use ($that) {
                $that->assertEquals($that->commandHandler->getTimeout(), $process->getTimeout());
            },
            $that->correctOutput_1 . $that->correctOutput_1 . $that->doublePrefixErrorOutput,
            function (Process $process, Command $command) use ($that) {
                $that->assertEquals($that->commandHandler->getTimeout(), $process->getTimeout());
            },
            $that->correctOutput_1 . $that->correctOutput_1 . $that->doublePrefixErrorOutput
        );
    }

    public function testAddHandlerWithoutLocalPropertiesWith_MERGE_NOT_DEFINED()
    {
        $that = $this;

        $this->createWithoutLocalProperties(
            CommandHandler::MERGE_NOT_DEFINED,
            function (Process $process, Command $command) use ($that) {
                $that->assertNull($process->getTimeout());
            },
            $that->correctOutput_1 . $that->correctOutput_1 . $that->correctOutput_2 . $that->correctOutput_2,
            function (Process $process, Command $command) use ($that) {
                if ($command->getCommand() == $that->correctCommand_2) {
                    $that->assertEquals($that->injectedHandlerTimeout, $process->getTimeout());
                } else {
                    $that->assertNull($process->getTimeout());
                }
            },
            $that->correctOutput_1 . $that->correctOutput_1 . $that->correctOutput_2 . $that->correctOutput_2
        );
    }

    public function testAddHandlerWithLocalPropertiesWith_MERGE_NOT_DEFINED()
    {
        $that = $this;

        $this->createWithLocalProperties(
            CommandHandler::MERGE_NOT_DEFINED,
            function (Process $process, Command $command) use ($that) {
                $that->assertEquals($that->commandHandler->getTimeout(), $process->getTimeout());
            },
            $that->correctOutput_1 . $that->correctOutput_1 . $that->doublePrefixErrorOutput,
            function (Process $process, Command $command) use ($that) {
                if ($command->getCommand() == $that->correctCommand_2) {
                    $that->assertEquals($that->injectedHandlerTimeout, $process->getTimeout());
                } else {
                    $that->assertEquals($that->commandHandler->getTimeout(), $process->getTimeout());
                }
            },
            $that->correctOutput_1 . $that->correctOutput_1 . $that->correctOutput_2 . $that->correctOutput_2
        );
    }

    public function testAddHandlerWithoutLocalPropertiesWith_MERGE_NON()
    {
        $that = $this;

        $this->createWithoutLocalProperties(
            CommandHandler::MERGE_NON,
            function (Process $process, Command $command) use ($that) {
                $that->assertNull($process->getTimeout());
            },
            $that->correctOutput_1 . $that->correctOutput_1 . $that->correctOutput_2 . $that->correctOutput_2,
            function (Process $process, Command $command) use ($that) {
                if ($command->getCommand() == $that->correctCommand_2) {
                    $that->assertEquals($that->injectedHandlerTimeout, $process->getTimeout());
                } else {
                    $that->assertNull($process->getTimeout());
                }
            },
            $that->correctOutput_1 . $that->correctOutput_1 . $that->correctOutput_2 . $that->correctOutput_2
        );
    }

    public function testAddHandlerWithLocalPropertiesWith_MERGE_NON()
    {
        $that = $this;

        $this->createWithLocalProperties(
            CommandHandler::MERGE_NON,
            function (Process $process, Command $command) use ($that) {
                if ($command->getCommand() == $that->correctCommand_2) {
                    $that->assertNull($process->getTimeout());
                } else {
                    $that->assertEquals($that->commandHandler->getTimeout(), $process->getTimeout());
                }
            },
            $that->correctOutput_1 . $that->correctOutput_1 . $that->correctOutput_2 . $that->correctOutput_2,
            function (Process $process, Command $command) use ($that) {
                if ($command->getCommand() == $that->correctCommand_2) {
                    $that->assertEquals($that->injectedHandlerTimeout, $process->getTimeout());
                } else {
                    $that->assertEquals($that->commandHandler->getTimeout(), $process->getTimeout());
                }
            },
            $that->correctOutput_1 . $that->correctOutput_1 . $that->correctOutput_2 . $that->correctOutput_2
        );
    }

    protected function createWithoutLocalProperties(
        $mergeType,
        $executeCallbackWithoutInjectedProperties,
        $outputWithoutInjectedProperties,
        $executeCallbackWithInjectedProperties,
        $outputWithInjectedProperties
    ) {
        $that = $this;

        $command_2 = $that->prepareCommandWithoutPrefix($this->correctCommand_2, $that->prefix);

        /**
         * Inject without prefix and timeout
         */
        $injectedHandler = $that->createHandler($that->correctCommand_2);

        $that->commandHandler = $that->createHandler($that->correctCommand_1);
        $that->commandHandler
            ->addHandler($injectedHandler, $mergeType, $mergeType)
            ->execute($executeCallbackWithoutInjectedProperties);

        $that->assertOutputEquals(
            $outputWithoutInjectedProperties
        );

        /**
         * Inject with prefix and timeout
         */
        $injectedHandler = $this->createHandler($command_2, $that->prefix, $that->injectedHandlerTimeout);

        $that->commandHandler = $that->createHandler($this->correctCommand_1);
        $that->commandHandler
            ->addHandler($injectedHandler, $mergeType, $mergeType)
            ->execute($executeCallbackWithInjectedProperties);

        $that->assertOutputEquals(
            $outputWithInjectedProperties
        );
    }

    protected function createWithLocalProperties(
        $mergeType,
        $executeCallbackWithoutInjectedProperties,
        $outputWithoutInjectedProperties,
        $executeCallbackWithInjectedProperties,
        $outputWithInjectedProperties
    ) {
        $that = $this;

        $command_1 = $that->prepareCommandWithoutPrefix($that->correctCommand_1, $that->prefix);

        $command_2 = $that->prepareCommandWithoutPrefix($this->correctCommand_2, $that->prefix);

        /**
         * Inject without prefix and timeout
         */
        $injectedHandler = $that->createHandler($that->correctCommand_2);

        $that->commandHandler = $that->createHandler($command_1, $that->prefix, $that->commandHandlerTimeout);
        $that->commandHandler
            ->addHandler($injectedHandler, $mergeType, $mergeType)
            ->execute($executeCallbackWithoutInjectedProperties);

        $that->assertOutputEquals(
            $outputWithoutInjectedProperties
        );

        /**
         * Inject with prefix and timeout
         */
        $injectedHandler = $this->createHandler($command_2, $that->prefix, $that->injectedHandlerTimeout);

        $that->commandHandler = $that->createHandler($command_1, $that->prefix, $that->commandHandlerTimeout);
        $that->commandHandler
            ->addHandler($injectedHandler, $mergeType, $mergeType)
            ->execute($executeCallbackWithInjectedProperties);

        $that->assertOutputEquals(
            $outputWithInjectedProperties
        );
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
     * @param  float  $timeout
     *
     * @return CommandHandler With two command ($command)
     */
    protected function createHandler($command = "", $prefix = "", $timeout = null)
    {
        $output = new BufferedOutput();

        $commandHandler = new CommandHandler($output, $prefix, $timeout);

        if ($command != "") {
            $commandHandler
                ->add($command)
                ->addCollection(array($command));
        }

        return $commandHandler;
    }
}