<?php
/**
 * Created by PhpStorm.
 * User: hnorbert
 * Date: 4/26/17
 * Time: 3:30 PM
 */

namespace Skillberto\CommandHandler\Factory;

use Skillberto\CommandHandler\CommandCollection;

class CommandCollectionFactory
{
    public function create(string $type = ''): CommandCollection
    {
        return new CommandCollection($type);
    }
}