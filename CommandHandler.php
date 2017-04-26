<?php

namespace Skillberto\CommandHandler;

use \Closure;
use Skillberto\CommandHandler\Executor\CommandExecutor;
use Skillberto\CommandHandler\Factory\CommandCollectionFactory;
use Skillberto\CommandHandler\Factory\CommandExecutorFactory;
use Symfony\Component\Console\Output\OutputInterface;

class CommandHandler
{
    const MERGE_NON = 0;

    const MERGE_NOT_DEFINED = 1;

    const MERGE_ALL = 2;

    const ALL_COMMAND = 'all';

    const SKIPPED_COMMAND = 'skipped';

    const ERROR_COMMAND = 'error';

    /**
     * @var CollectionManager
     */
    protected $collectionManager;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var float|int|null
     */
    protected $timeout = null;

    /**
     * @var string
     */
    protected $prefix = '';

    /**
     * @var CommandExecutorFactory
     */
    protected $executorFactory;

    /**
     * @param OutputInterface $outputInterface
     * @param string          $prefix
     * @param int|float|null  $timeout         The timeout in seconds
     */
    public function __construct(OutputInterface $outputInterface, $prefix = '', $timeout = null)
    {
        $this->output = $outputInterface;
        $this->prefix = $prefix;
        $this->timeout = $timeout;
        $this->executorFactory = new CommandExecutorFactory($this->output);
        $this->collectionManager = new CollectionManager(new CommandCollectionFactory(), $this->output);
    }

    /**
     * @param int|float|null $timeout The timeout in seconds
     *
     * @return $this
     */
    public function setTimeout($timeout = null)
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * @return float
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @param string $prefix
     *
     * @return $this
     */
    public function addPrefix($prefix = '')
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @return OutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @param Command $command
     *
     * @return $this
     */
    public function addCommand(Command $command)
    {
        $data = $this->prefix.$command->getCommand();

        $command->setCommand($data);

        if (null === $command->getTimeout()) {
            $command->setTimeout($this->getTimeout());
        }

        $this->collectionManager->getCollection(self::ALL_COMMAND)->add($command);

        return $this;
    }

    /**
     * @param array $commands Command collection
     *
     * @return $this
     */
    public function addCommands(array $commands)
    {
        foreach ($commands as $command) {
            $this->addCommand($command);
        }

        return $this;
    }

    /**
     * @return CommandCollection
     */
    public function getCommands(): CommandCollection
    {
        return $this->collectionManager->getCollection(self::ALL_COMMAND);
    }

    /**
     * @param string $commandString
     *
     * @return $this
     */
    public function add(string $commandString)
    {
        $command = $this->createCommand($commandString);

        $this->addCommand($command);

        return $this;
    }

    /**
     * @param array $commandStrings
     *
     * @return $this
     */
    public function addCollection(array $commandStrings)
    {
        foreach ($commandStrings as $commandString) {
            $this->add($commandString);
        }

        return $this;
    }

    /**
     * @param string $commandString
     *
     * @return $this
     */
    public function addSkippable($commandString)
    {
        $command = $this->createCommand($commandString, false);

        $this->addCommand($command);

        return $this;
    }

    /**
     * @param array $commandStrings
     *
     * @return $this
     */
    public function addSkippableCollection(array $commandStrings)
    {
        foreach ($commandStrings as $commandString) {
            $this->addSkippable($commandString);
        }

        return $this;
    }

    /**
     * Merge an other CommandHandler with this.
     * Everything used from the injected handler for that commands, except the prefix and timeout. They are optional.
     *
     * If $mergePrefix or $mergeTimeout params are MERGE_ALL, then will used the current property.
     * If MERGE_NOT_DEFINED, then will used the current property if the injected not defined, otherwise use the injected property.
     *
     * @param CommandHandler $handler An other CommandHandler instance
     * @param int            $prefix  MERGE_ALL | MERGE_NOT_DEFINED | MERGE_NON
     * @param int            $timeout MERGE_ALL | MERGE_NOT_DEFINED | MERGE_NON
     *
     * @return $this
     */
    public function addHandler(CommandHandler $handler, $mergePrefix = self::MERGE_NON, $mergeTimeout = self::MERGE_NON)
    {
        foreach ($handler->getCommands() as $command) {
            $internalPrefix = ($mergePrefix == self::MERGE_ALL || ($mergePrefix == self::MERGE_NOT_DEFINED && $handler->getPrefix() == '')) ? $this->getPrefix() : '';
            $internalTimeout = ($mergeTimeout == self::MERGE_ALL || ($mergeTimeout == self::MERGE_NOT_DEFINED && $command->getTimeout() === null)) ? $this->getTimeout() : $command->getTimeout();

            $data = $internalPrefix.$command->getCommand();

            $command->setCommand($data);
            $command->setTimeout($internalTimeout);

            $this->collectionManager->getCollection(self::ALL_COMMAND)->add($command);
        }

        return $this;
    }

    /**
     * @param Closure|null $callback
     *
     * @return CommandHandler
     */
    public function execute(Closure $callback = null): CommandHandler
    {
        $skippedCollection = $this->collectionManager->getCollection(self::SKIPPED_COMMAND);

        $executor = $this->createExecutor($skippedCollection);

        foreach ($this->getCommands() as $command) {
            if (! $executor->execute($command, $callback)) {
                $this->collectionManager->getCollection(self::ERROR_COMMAND)->add($command);

                return $this;
            }
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function hasSkipped(): bool
    {
        return $this->collectionManager->hasCollection(self::SKIPPED_COMMAND);
    }

    /**
     * @return bool
     */
    public function hasError(): bool
    {
        return $this->collectionManager->hasCollection(self::ERROR_COMMAND);
    }

    /**
     * Show skipped messages
     */
    public function showSkippedMessages(): void
    {
        $this->collectionManager->showMessages(self::SKIPPED_COMMAND);
    }

    /**
     * Show error messages
     */
    public function showErrorMessages(): void
    {
        $this->collectionManager->showMessages(self::ERROR_COMMAND);
    }


    /**
     * @param string         $command
     * @param bool           $required
     * @param int|float|null $timeout  The timeout in seconds
     *
     * @return Command
     */
    protected function createCommand($command, $required = true, $timeout = null): Command
    {
        return new Command($command, $required, $timeout);
    }

    /**
     * @param CommandCollection $skippedCommandCollection
     *
     * @return CommandExecutor
     */
    protected function createExecutor(CommandCollection $skippedCommandCollection): CommandExecutor
    {
        return $this->executorFactory->create($skippedCommandCollection);
    }
}
