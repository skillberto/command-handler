# Command Handler
[![Build Status](https://travis-ci.org/skillberto/commandhandler.svg?branch=master)](https://travis-ci.org/skillberto/commandhandler)

## Install

Install from composer:
```
$ composer require skillberto/command-handler "~1.0"
```

## Authors and contributors
* [Norbert Heiszler](heiszler.norbert@gmail.com) (Creator, developer)

## Usage

The following example show you how you can us it:
```
use Symfony\Component\Console\Output\ConsoleOutput;
use Skillberto\CommandHandler\CommandHandler;
use Skillberto\CommandHandler\Command;

$output = new ConsoleOutput();
$handler = new CommandHandler($output);
$handler->add( 'some commands' );

... or

$handler->addCommand(new Command('some commands'));
$handler->execute();
```

You can define collections too:
```
$handler->addCollection(array('some commands'));

... or

$handler->addCommands(array(new Command('some commands')));
```

If you want to skip a command if it's not required:
```
$handler->addSkippable('some commands');

... or

$handler->addCommand(new Command('some commands', false));

... or add more commands

$handler->addSkippableCollection( array( 'some commands' ) );

... or

$handler->addCommands(array(new Command('some commands', false)));

$handler->execute();
```

...after that you can get these commands:
```
$handler->getSkippedMessages();
```
But, if you don't skip a command, and it's not successful:
```
$handler->getErrorMessage();

```
## Advanced usage
### Prefix

The following example show you have can you use the prefix:

```
...

$handler = new CommandHandler($output, "php ");

... or

$handler = new CommandHandler($output);
$handler->addPrefix("php ");

... then

$handler->add("--version");
$handler->execute();
```
In this case, you will execute ```php --version```.

### Timeout

It can be defined for each command, or only for some commands, or both of them.
```
$handler = new CommandHandler($output, "", 0.2);

... or

$handler = new CommandHandler($output);
$handler->setTimeout(0.2);

... then

$handler->addCommand(new Command("php --version", true, 0.3);
$handler->add('something');
$handler->execute();
```
In the previous example every command will have "0.2 seconds" for execution, except ```php --version```, it has got "0.3 seconds".

Let's see how can you define group timeout:
```
$handler = new CommandHandler($output);
$handler->setTimeout(0.2);
$handler->addCollection(array('some command'));
$handler->setTimeout(0.3);
$handler->addCollection(array('some command'));
$handler->execute();
```

In this example the first collection have "0.2 seconds", the second "0.3 seconds".

### Handler injection, merge
In some case, we need to define more then one handler, eg.: for different prefixes.
But don't worry about it, we have got a useful method:
```
$handler_1->addHandler($handler_2);
```
It's good, but what will be with prefixes and timeout?
For these problems, CommandHandler has got three different merge types:
```MERGE_ALL, MERGE_NON, MERGE_NOT_DEFINED```
The default is ```MERGE_NON``, but you can change it:
```
$handler_1->addHandler($handler_2, CommandHandler::MERGE_ALL, CommandHandler::MERGE_NOT_DEFINED);
```
In the previous example prefix merge has got ```MERGE_ALL``` type, timeout has got ```MERGE_NOT_DEFINED``` type.
```MERGE_ALL``` means that ```$handler_1``` prefix or/and timeout will use for all of them.
```MERGE_NOT_DEFINED``` means that ```$handler_1``` prefix or/and timeout will use, if it's not defined for ```$handler_2``` command(s).
```MERGE_NON``` means that prefixes and timeouts will be separated.

### Callback

You can define a callback for each execution:
```
...
use Symfony\Component\Process\Process;
use Skillberto\CommandHandler\Command;
...

$handler->execute(function(Progress $progress, Command $command) {
   //something more
});
```
