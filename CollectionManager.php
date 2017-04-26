<?php
/**
 * Created by PhpStorm.
 * User: hnorbert
 * Date: 4/26/17
 * Time: 4:20 PM
 */

namespace Skillberto\CommandHandler;

use Skillberto\CommandHandler\Factory\CommandCollectionFactory;

class CollectionManager
{
    /**
     * @var CommandCollection[]
     */
    protected $collections = [];

    /**
     * @var CommandCollectionFactory
     */
    protected $collectionFactory;

    /**
     * CollectionManager constructor.
     *
     * @param CommandCollectionFactory $collectionFactory
     */
    public function __construct(CommandCollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @param string $type
     *
     * @return CommandCollection
     */
    public function getCollection(string $type): CommandCollection
    {
        if (! $this->hasCollection($type)) {
            $skippedCommandCollection = $this->collectionFactory->create($type);

            $this->collections[$type] = $skippedCommandCollection;

            return $skippedCommandCollection;
        }

        return $this->collections[$type];
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    public function hasCollection(string $type): bool
    {
        return array_key_exists($type, $this->collections);
    }
}