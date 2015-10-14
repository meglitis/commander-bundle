<?php

namespace Guscware\CommanderBundle\Command;

/**
 * Interface LockableCommandInterface
 *
 * By implementing this interface in a Command, Symfony's event listener will listen for command
 * events for execute, exception and terminate, and create, check and remove a .lock file accordingly
 *
 * The .lock file lives for 5 minutes by default, which means that if the command died unexpectedly,
 * it won't be able to launch again until those 5 minutes have passed.
 * You can configure the lifetime of the lockfile by passing the "auto_unlock_after" config parameter
 * and specifying an amount in seconds.
 */
interface LockableCommandInterface
{
}