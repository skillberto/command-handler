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
$handler->addCommand( 'some kind of command' );

...
or add more commands
...

$handler->addCommands( array( 'some kind of commands' ) );

$handler->execute();
```
If you want to skip a command if it's not successful (and not needed):
```
$handler->addSkippableCommand('some kind of command');

...
or add more commands

...
$handler->addSkippableCommands( array( 'some kind of commands' ) );

$handler->execute();
```
...after that you can get these commands:
```
if ($handler->hasSkipped()) {
    $handler->getSkippedMessages();
}
```
But, if you don't skip a command, and it's not successful:
```
$handler->execute();

if ($handler->hasError()) {
    $handler->getErrorMessage();
}
```