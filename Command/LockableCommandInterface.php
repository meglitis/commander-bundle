<?php

namespace Guscware\CommanderBundle\Command;

interface LockableCommandInterface
{
    /**
     * Return the amount of seconds a lockfile will auto-unlock in
     *
     * e.g. - if it returns 30, then even if the command is still running in 30 seconds, another instance
     * will be able to run, since the modification date of the lockfile will have outlived itself
     *
     * For 30 seconds though no other process will be able to run this command in parallel
     *
     * @return int
     */
    public function getLockTimeToLiveInSeconds();
}