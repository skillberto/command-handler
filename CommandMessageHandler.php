<?php
/**
 * Created by PhpStorm.
 * User: hnorbert
 * Date: 4/26/17
 * Time: 5:25 PM
 */

namespace Skillberto\CommandHandler;


use Symfony\Component\Console\Output\OutputInterface;

class CommandMessageHandler
{
    protected $output;

    protected $collectionManager;

    public function __construct(OutputInterface $output, CollectionManager $collectionManager)
    {
        $this->output = $output;
        $this->collectionManager = $collectionManager;
    }

    /**
     * Show messages by type, if collection exists
     *
     * @param string $type
     */
    public function showMessages(string $type): void
    {
        if (! $this->hasCommandInCollection($type)) {
            return;
        }

        foreach ($this->collectionManager->getCollection($type) as $command) {
            $this->info($command, strtoupper($type));
        }
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    public function hasCommandInCollection(string $type): bool
    {
        if (! $this->collectionManager->hasCollection($type)) {
            return false;
        }

        return $this->collectionManager->getCollection($type)->count() > 0;
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