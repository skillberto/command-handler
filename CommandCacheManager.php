<?php
/**
 * Created by PhpStorm.
 * User: hnorbert
 * Date: 4/26/17
 * Time: 5:53 PM
 */

namespace Skillberto\CommandHandler;


class CommandCacheManager
{
    protected $collectionManager;

    protected $allTypeName;

    protected $cachedTypeName;

    public function __construct(CollectionManager $collectionManager, string $allTypeName, string $cachedTypeName)
    {
        $this->collectionManager = $collectionManager;
        $this->allTypeName = $allTypeName;
        $this->cachedTypeName = $cachedTypeName;
    }

    /**
     * Return all command.
     *
     * @return CommandCollection
     */
    public function getCommandsCollection()
    {
        return $this->collectionManager->getCollection($this->allTypeName);
    }

    /**
     * Return commands what need to execute
     * All commands - cached commands
     *
     * @return CommandCollection
     */
    public function getExecutableCollection(): CommandCollection
    {
        //TODO
    }

    // Not Need to run, cached
    public function getCachedCollection()
    {
        return $this->collectionManager->getCollection($this->cachedTypeName);
    }

    // Put into cache (not need to run)
    public function cache(Command $command)
    {
        $this->getCachedCollection()->add($command);
    }

    // Reset cache, everything need to run
    public function resetCache()
    {
        $this->collectionManager->getCollection($this->cachedTypeName)->reset();
    }
}