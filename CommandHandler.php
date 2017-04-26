<?php

namespace Skillberto\CommandHandler;

use \Closure;
use Skillberto\CommandHandler\Executor\CommandExecutor;
use Skillberto\CommandHandler\Factory\CommandCollectionFactory;
use Skillberto\CommandHandler\Factory\CommandExecutorFactory;
use Symfony\Component\Console\Output\OutputInterface;

class CommandHandler
{
    const ALL_COMMAND = 'all';

    const SKIPPED_COMMAND = 'skipped';

    const ERROR_COMMAND = 'error';

    const CACHED_COMMAND = 'cached';

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
     * @var CommandMessageHandler
     */
    protected $messageHandler;

    /**
     * @var CommandCacheManager
     */
    protected $cacheManager;

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
        $this->collectionManager = new CollectionManager(new CommandCollectionFactory());
        $this->messageHandler = new CommandMessageHandler($this->output, $this->collectionManager);
        $this->cacheManager = new CommandCacheManager($this->collectionManager, self::ALL_COMMAND, self::CACHED_COMMAND);
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
    public function getCommandsCollection(): CommandCollection
    {
        return $this->cacheManager->getCommandsCollection();
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
     * @return CommandHandler
     */
    public function addHandler(CommandHandler $handler, $mergePrefix = CommandHandlerMerger::MERGE_NON, $mergeTimeout = CommandHandlerMerger::MERGE_NON): CommandHandler
    {
        CommandHandlerMerger::merge($this, $handler, $mergePrefix, $mergeTimeout);

        return $this;
    }

    /**
     * @param Closure|null $callback
     *
     * @return CommandHandler
     */
    public function execute(Closure $callback = null, bool $cache = true): CommandHandler
    {
        $skippedCollection = $this->collectionManager->getCollection(self::SKIPPED_COMMAND);

        if (! $cache) {
            $this->cacheManager->resetCache();
        } else {
            $this->messageHandler->showMessages(self::CACHED_COMMAND);
        }

        $executableCollection = $this->cacheManager->getExecutableCollection();

        $executor = $this->createExecutor($skippedCollection);

        foreach ($executableCollection as $command) {
            if (! $executor->execute($command, $callback)) {
                $this->collectionManager->getCollection(self::ERROR_COMMAND)->add($command);

                return $this;
            }

            $this->cacheManager->cache($command);
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function hasSkipped(): bool
    {
        return $this->messageHandler->hasCommandInCollection(self::SKIPPED_COMMAND);
    }

    /**
     * @return bool
     */
    public function hasError(): bool
    {
        return $this->messageHandler->hasCommandInCollection(self::ERROR_COMMAND);
    }

    /**
     * Show skipped messages
     */
    public function showSkippedMessages(): void
    {
        $this->messageHandler->showMessages(self::SKIPPED_COMMAND);
    }

    /**
     * Show error messages
     */
    public function showErrorMessages(): void
    {
        $this->messageHandler->showMessages(self::ERROR_COMMAND);
    }


    /**
     * @param string    $command
     * @param bool      $required
     * @param int       $timeout  The timeout in seconds
     *
     * @return Command
     */
    protected function createCommand(string $command, bool $required = true, int $timeout = null): Command
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
