<?php

namespace Guscware\CommanderBundle\Component;

use Guscware\CommanderBundle\Exceptions\CommanderException;

class CommanderUtilities
{
    /**
     * @param object $command
     *
     * @return string
     *
     * @throws CommanderException
     */
    public static function getClassNameWithHash($command)
    {
        if (!is_object($command)) {
            throw new CommanderException("Attempting to get hash of non-object");
        }

        $reflectionClass = new \ReflectionClass(get_class($command));
        $className       = $reflectionClass->getShortName();

        $hash     = sha1(get_class($command));
        $tinyHash = substr($hash, 0, 7);

        $classNameWithHash = sprintf('%s_%s', $className, $tinyHash);

        return $classNameWithHash;
    }
}