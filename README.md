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
use \Symfony\Component\Console\Output\ConsoleOutput;
use \Skillberto\CommandHandler\CommandHandler;
use \Skillberto\CommandHandler\Command;

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
In the previous example every command will have "0.2 seconds" for execution, except "php --version", it has got "0.3 seconds".

Let's see how can define group timeout:
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