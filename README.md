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

$output = new ConsoleOutput();
$handler = new CommandHandler($output);
$handler->add( 'some commands' );

...
or add more commands
...

$handler->addCollection( array( 'some commands' ) );

$handler->execute();
```
If you want to skip a command if it's not required:
```
$handler->addSkippable('some commands');

...
or add more commands

...
$handler->addSkippableCollection( array( 'some commands' ) );

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

$handler = new CommandHandler($output, "php ")
$handler->add("--version");
$handler->execute();
```
In this case, you will execute ```php --version```.