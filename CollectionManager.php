<?php
/**
 * Created by PhpStorm.
 * User: hnorbert
 * Date: 4/26/17
 * Time: 4:20 PM
 */

namespace Skillberto\CommandHandler;

use Skillberto\CommandHandler\Factory\CommandCollectionFactory;
use Symfony\Component\Console\Output\OutputInterface;

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
     * @var OutputInterface
     */
    protected $output;

    /**
     * CollectionManager constructor.
     *
     * @param CommandCollectionFactory $collectionFactory
     * @param OutputInterface $output
     */
    public function __construct(CommandCollectionFactory $collectionFactory, OutputInterface $output)
    {
        $this->collectionFactory = $collectionFactory;
        $this->output = $output;
    }

    /**
     * @param string $type
     *
     * @return CommandCollection
     */
    public function getCollection(string $type): CommandCollection
    {
        if (! array_key_exists($type, $this->collections)) {
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

    /**
     * Show messages by type, if collection exists
     *
     * @param string $type
     */
    public function showMessages(string $type): void
    {
        if (! $this->hasCollection($type)) {
            return;
        }

        foreach ($this->getCollection($type) as $command) {
            $this->info($command, strtoupper($type));
        }
    }

    /**
     * @param Command $command
     * @param string  $info
     */
    protected function info(Command $command, string  $info): void
    {
        $this->output->writeln(sprintf('<info>%s:</info> %s', $info, $command->getCommand()));
    }
}