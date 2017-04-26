<?php
/**
 * Created by PhpStorm.
 * User: hnorbert
 * Date: 4/26/17
 * Time: 4:46 PM
 */

namespace Skillberto\CommandHandler;

class CommandHandlerMerger
{
    const MERGE_NON = 0;

    const MERGE_NOT_DEFINED = 1;

    const MERGE_ALL = 2;

    /**
     * Merge two handler with prefix and timeout change options
     *
     * @param CommandHandler $commandHandler_1
     * @param CommandHandler $commandHandler_2
     *
     * @param int $mergePrefix
     * @param int $mergeTimeout
     */
    public static function merge(CommandHandler $commandHandler_1, CommandHandler $commandHandler_2, int $mergePrefix = self::MERGE_NON, int $mergeTimeout = self::MERGE_NON)
    {
        if ($commandHandler_2->getCommandsCollection()->count() == 0) {
            return;
        }

        /**
         * @var Command $command
         */
        foreach ($commandHandler_2->getCommandsCollection() as $command) {
            $internalPrefix = ($mergePrefix == self::MERGE_ALL || ($mergePrefix == self::MERGE_NOT_DEFINED && $commandHandler_2->getPrefix() == '')) ? $commandHandler_1->getPrefix() : '';
            $internalTimeout = ($mergeTimeout == self::MERGE_ALL || ($mergeTimeout == self::MERGE_NOT_DEFINED && $command->getTimeout() === null)) ? $commandHandler_1->getTimeout() : $command->getTimeout();

            $data = $internalPrefix . $command->getCommand();

            $command->setCommand($data);
            $command->setTimeout($internalTimeout);

            $commandHandler_1->getCommandsCollection()->add($command);
        }
    }
}