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

    protected $correctCommand_1 = "php ./Tests/Resources/test_correct_1.php";

    protected $correctCommand_2 = "php ./Tests/Resources/test_correct_2.php";

    protected $wrongCommand = "php ./Tests/Resources/test_wrong.php";

    protected $correctOutput = "Executing: php ./Tests/Resources/test_correct_1.php\nfoo";

    protected $wrongOutput = "Executing: php ./Tests/Resources/test_wrong.php\n";

    protected $skipOutput = "Skipped: php ./Tests/Resources/test_wrong.php\n";

    protected $errorOutput = "Error: php ./Tests/Resources/test_wrong.php\n";

    protected function setUp()
    {
        $output = new BufferedOutput();

        $this->commandHandler = new CommandHandler($output);
        $this->commandHandler
            ->add($this->correctCommand_1)
            ->addCollection(array($this->correctCommand_1));
    }

    public function testCorrectCommandWithoutSkip()
    {
        $this->commandHandler->execute();

        $this->assertOutputEquals(
            $this->correctOutput. $this->correctOutput
        );
    }

    public function testCorrectCommandWithSkip()
    {
        $this->commandHandler
            ->addSkippable($this->correctCommand_1)
            ->addSkippableCollection(array($this->correctCommand_1))
            ->execute();

        $this->commandHandler->getSkippedMessages();
        $this->commandHandler->getErrorMessage();

        $this->assertOutputEquals(
            $this->correctOutput . $this->correctOutput . $this->correctOutput. $this->correctOutput
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

        $this->commandHandler->getSkippedMessages();
        $this->commandHandler->getErrorMessage();

        $this->assertOutputEquals(
            $this->correctOutput . $this->correctOutput . $this->wrongOutput. $this->skipOutput
        );

    }

    public function testWrongCommandWithoutSkipAsError()
    {
        $this->commandHandler
            ->add($this->wrongCommand)
            ->execute();

        $this->commandHandler->getSkippedMessages();
        $this->commandHandler->getErrorMessage();

        $this->assertOutputEquals(
            $this->correctOutput . $this->correctOutput . $this->wrongOutput. $this->errorOutput
        );

    }

    public function testPrefix()
    {
        $prefix = 'php ';
        $correctCommand_1 = $this->correctCommand_1;

        if (substr($correctCommand_1, 0, strlen($prefix)) == $prefix) {
            $correctCommand_1 = substr($correctCommand_1, strlen($prefix));
        } else {
            throw new \InvalidArgumentException(sprintf('%s command is incorrect, %s prefix is not found at the beginning.', $correctCommand_1, $prefix));
        }

        $output = new BufferedOutput();

        $this->commandHandler = new CommandHandler($output, $prefix);
        $this->commandHandler
            ->add($correctCommand_1)
            ->addCollection(array($correctCommand_1))
            ->execute();

        $this->assertOutputEquals(
            $this->correctOutput. $this->correctOutput
        );
    }

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
        $this->assertEquals(
            $this->formatOutput($expected),
            $this->formatOutput($this->commandHandler->getOutput()->fetch())
        );
    }
}