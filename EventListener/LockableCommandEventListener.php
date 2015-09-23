<?php

namespace Guscware\CommanderBundle\EventListener;

use Guscware\CommanderBundle\Command\LockableCommandInterface;
use Guscware\CommanderBundle\Component\CommanderUtilities;
use Guscware\CommanderBundle\Exceptions\LockFileDirectoryException;
use Guscware\CommanderBundle\Exceptions\LockFilePresentException;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleExceptionEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;

class LockableCommandEventListener
{
    /** @var string */
    private $lockFileDirPath;

    /**
     * LockableCommandEventListener constructor.
     *
     * @param string $lockFileDir
     *
     * @throws LockFileDirectoryException
     */
    public function __construct($lockFileDir)
    {
        if (!file_exists($lockFileDir) || !is_dir($lockFileDir)) {
            $dirCreated = mkdir($lockFileDir, 0770, true);
            if (!$dirCreated) {
                throw new LockFileDirectoryException(sprintf('Cannot create lockfile directory in "%s"', $lockFileDir));
            }
        }

        $this->lockFileDirPath = $lockFileDir;
    }

    /**
     * Checks if a lockfile for the called class exists
     *  - If it does - compares $lockFileLifespanInSeconds with the $lockFileLifeTime
     *    if $lockFileLifeTime < $lockFileLifespanInSeconds, the command is not allowed to execute
     *    else - the existing lockfile mtime gets updated and the command is executed
     *  - If no lockfile exists, one is created
     *
     * @param ConsoleCommandEvent $event
     *
     * @throws LockFilePresentException
     */
    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        $command = $event->getCommand();

        if ($command instanceof LockableCommandInterface) {
            $lockFileLifespanInSeconds = (int)$command->getLockTimeToLiveInSeconds();
            $lockFilePath = $this->getLockFilePathFromClass($command);

            if (file_exists($lockFilePath)) {
                $modificationTime = filemtime($lockFilePath);
                $lockFileLifeTime = time() - $modificationTime;

                if ($lockFileLifeTime < $lockFileLifespanInSeconds) {
                    $secondsUntilUnlock = $lockFileLifespanInSeconds - $lockFileLifeTime;

                    throw new LockFilePresentException(
                        sprintf(
                            "Lockfile present. <comment>%dm %ds</comment> until automatic unlock",
                            floor(($secondsUntilUnlock) / 60),
                            ($secondsUntilUnlock) % 60
                        )
                    );
                }
            }

            clearstatcache();
            touch($lockFilePath) or die ('Cannot create lock file');
        }
    }


    /**
     * @param ConsoleTerminateEvent $event
     */
    public function onConsoleTerminate(ConsoleTerminateEvent $event)
    {
        $command = $event->getCommand();
        if ($command instanceof LockableCommandInterface) {
            $this->removeLockFileFor($command);
        }
    }


    /**
     * @param ConsoleExceptionEvent $event
     */
    public function onConsoleException(ConsoleExceptionEvent $event)
    {
        $command = $event->getCommand();
        if ($command instanceof LockableCommandInterface) {
            $this->removeLockFileFor($command);
        }
    }

    /**
     * Removes the lockfile for a given command
     *
     * @param LockableCommandInterface $command
     */
    private function removeLockFileFor(LockableCommandInterface $command)
    {
        $lockFilePath = $this->getLockFilePathFromClass($command);
        if (file_exists($lockFilePath)) {
            unlink($lockFilePath);
        }
    }


    /**
     * Gets a lockfile name based on the $command class' shortName()
     *
     * @param LockableCommandInterface $command
     *
     * @return string
     */
    private function getLockFilePathFromClass(LockableCommandInterface $command)
    {
        $classNameWithHash = CommanderUtilities::getClassNameWithHash($command);
        $path = $this->lockFileDirPath;

        $lockFilePath = $path . '/' . $classNameWithHash . '.lock';

        return $lockFilePath;
    }
}