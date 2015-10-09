# commander-bundle [![Build Status](https://travis-ci.org/guscware/commander-bundle.svg)](https://travis-ci.org/guscware/commander-bundle)

Adds more functionality to Symfony 2 commands. Command locking, and various IO helpers.

1. Installation
---------------

Use composer:  
```bash
$ composer require guscware/commander-bundle
```

After downloading, add the bundle to your `AppKernel.php`  
```php
$bundles = array(
  // ..
  new Guscware\CommanderBundle\CommanderBundle(),
);
```

2. Usage instructions
---------------------

### Extending Commander  

Extend the `Commander` class in your Commands

```php
class YourGreatCommand extends Commander
{
  // ..
}
```

By extending it, you get an array of various helper methods

> `$this->write()` - this method takes three arguments

1. `$string` - a string which will be written to `OutputInterface`
  * defaults to empty string
2. `$verbosityLevel` - `int` which determines at which verbosity level this string will be output at
  * `VERBOSITY_LEVEL_NORMAL` - if QUIET or any of the `--verbose` flags aren't passed (default)
  * `VERBOSITY_LEVEL_VERBOSE` - if the `--verbose` or `-v` flag is passed
  * `VERBOSITY_LEVEL_VERY_VERBOSE` - if the `-vv` flag is passed
  * `VERBOSITY_LEVEL_DEBUG` - if the `-vvv` flag is passed
3. `$verbosityStrategy` - with this you define what other verbosity levels will be able to receive this output
  * `at_or_above` - output will receive `$string` if the current verbosity level is at `$verbosityLevel` or above (default)
  * `at_or_below` - output will receive `$string` if the current verbosity level is at `$verbosityLevel` or below
  * `exact` - means that no other verbosity levels will be able to receive this `$stirng`

Constants for these values are provided in the `Commander` class

> `$this->writeln()` - an alias to `$this->write()` except it appends `\n` to the `$string`

### Using `LockableCommandInterface`

By implementing the `LockableCommandInterface` in any `Command` you prevent that command from being executed more than once at the same time.

You must implement its' `getLockTimeToLiveInSeconds()` method, which returns an `int` of seconds for how long the lock should be present while the command is executing

```php
class VeryImportantSingleProcessCommand implements LockableCommandInterface
{
  public function getLockTimeToLiveInSeconds()
  {
    return 20; // 20 seconds of lifetime
  }
}
```

During those 20 seconds no other instance of that method will be able to run.
20 seconds of lifetime means that if you run the command in another process after 20 seconds, even if the previous command call is still running - the second one will disregard the lockfile and run refreshing the lockfile once more.
