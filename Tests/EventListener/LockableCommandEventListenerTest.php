<?php

namespace Guscware\CommanderBundle\Tests\EventListener;

use Guscware\CommanderBundle\Command\LockableCommandInterface;
use Guscware\CommanderBundle\Component\CommanderUtilities;
use Guscware\CommanderBundle\EventListener\LockableCommandEventListener;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleExceptionEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Output\ConsoleOutput;

class LockableCommandEventListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var string */
    private static $lockFileDir;

    public static function setUpBeforeClass()
    {
        self::$lockFileDir = __DIR__ . '/lockfiles';
    }

    protected function setUp()
    {
        if (!file_exists(self::$lockFileDir)) {
            mkdir(self::$lockFileDir, 0777, true);
        }

        if (!is_dir(self::$lockFileDir)) {
            $this->fail(sprintf("'%s' is not a directory"));
        }

        $dir = new \DirectoryIterator(self::$lockFileDir);
        foreach ($dir as $fileinfo) {
            if (!$fileinfo->isDot()) {
                if (preg_match('/.*\.lock$/', $fileinfo->getFilename())) {
                    unlink($fileinfo->getRealPath());
                }
            }
        }
    }

    /**
     * @expectedException \Guscware\CommanderBundle\Exceptions\LockFilePresentException
     * @expectedExceptionMessage Lockfile present. <comment>0m 20s</comment> until automatic unlock
     *
     * @throws \Guscware\CommanderBundle\Exceptions\LockFilePresentException
     */
    public function testLockFileWorksOnSecondRun()
    {
        $lockableListener = new LockableCommandEventListener(self::$lockFileDir);
        $commandMock      = $this->getCommandMockForCommand();
        $commandEventMock = $this->getConsoleCommandEventMock($commandMock);
        $lockfile         = $this->getCommandLockfilePath($commandMock);

        $lockableListener->onConsoleCommand($commandEventMock);
        $this->assertNotEmpty($lockfile);
        $this->assertFileExists($lockfile);
        $lockableListener->onConsoleCommand($commandEventMock);
    }

    public function testOldLockfileMTimeOverwritten()
    {
        $lockableListener = new LockableCommandEventListener(self::$lockFileDir);
        $commandMock      = $this->getCommandMockForCommand();
        $commandEventMock = $this->getConsoleCommandEventMock($commandMock);
        $lockfile         = $this->getCommandLockfilePath($commandMock);

        touch($lockfile, time() - 30);
        $this->assertFileExists($lockfile);
        $oldMTime = filemtime($lockfile);

        $lockableListener->onConsoleCommand($commandEventMock);
        $this->assertFileExists($lockfile);

        $newMTime = filemtime($lockfile);

        $this->assertSame(30, $newMTime - $oldMTime);
    }

    public function testLockFileDeletedAfterSuccessfulRun()
    {
        $lockableListener   = new LockableCommandEventListener(self::$lockFileDir);
        $commandMock        = $this->getCommandMockForTerminate();
        $terminateEventMock = $this->getConsoleTerminateEventMock($commandMock);
        $lockfile           = $this->getCommandLockfilePath($commandMock);

        touch($lockfile);
        $this->assertFileExists($lockfile);

        $lockableListener->onConsoleTerminate($terminateEventMock);
        $this->assertFileNotExists($lockfile);
    }

    /**
     * @expectedException \Guscware\CommanderBundle\Exceptions\LockFilePresentException
     *
     * @throws \Guscware\CommanderBundle\Exceptions\LockFilePresentException
     */
    public function testLockFileNotDeletedAfterLockFilePresentException()
    {
        $lockableListener = new LockableCommandEventListener(self::$lockFileDir);
        $commandMock      = $this->getCommandMockForCommand();
        $commandEventMock = $this->getConsoleCommandEventMock($commandMock);
        $lockfile         = $this->getCommandLockfilePath($commandMock);

        touch($lockfile);
        $this->assertFileExists($lockfile);

        $lockableListener->onConsoleCommand($commandEventMock);

        $this->assertFileExists($lockfile);
    }

    /**
     * @param TestableLockableCommand $command
     *
     * @return string
     */
    private function getCommandLockfilePath(TestableLockableCommand $command)
    {
        $classNameWithHash = CommanderUtilities::getClassNameWithHash($command);

        return self::$lockFileDir . '/' . $classNameWithHash . '.lock';
    }

    /**
     * @param TestableLockableCommand $commandMock
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|ConsoleCommandEvent
     */
    private function getConsoleCommandEventMock(TestableLockableCommand $commandMock)
    {
        /** @var ConsoleCommandEvent|\PHPUnit_Framework_MockObject_MockObject $outputInterface */
        $consoleCommandEvent = $this
            ->getMockBuilder(ConsoleCommandEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $consoleCommandEvent
            ->expects($this->atLeastOnce())
            ->method('getCommand')
            ->willReturn($commandMock);

        return $consoleCommandEvent;
    }

    /**
     * @param TestableLockableCommand $commandMock
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|ConsoleTerminateEvent
     */
    private function getConsoleTerminateEventMock(TestableLockableCommand $commandMock)
    {
        /** @var ConsoleTerminateEvent|\PHPUnit_Framework_MockObject_MockObject $outputInterface */
        $consoleCommandEvent = $this
            ->getMockBuilder(ConsoleTerminateEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $consoleCommandEvent
            ->expects($this->atLeastOnce())
            ->method('getCommand')
            ->willReturn($commandMock);

        return $consoleCommandEvent;
    }

    /**
     * @param TestableLockableCommand $commandMock
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|ConsoleExceptionEvent
     */
    private function getConsoleExceptionEventMock(TestableLockableCommand $commandMock)
    {
        /** @var ConsoleExceptionEvent|\PHPUnit_Framework_MockObject_MockObject $outputInterface */
        $consoleCommandEvent = $this
            ->getMockBuilder(ConsoleExceptionEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $consoleCommandEvent
            ->expects($this->atLeastOnce())
            ->method('getCommand')
            ->willReturn($commandMock);

        return $consoleCommandEvent;
    }

    /**
     * @return TestableLockableCommand|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getCommandMockForCommand()
    {
        /** @var TestableLockableCommand|\PHPUnit_Framework_MockObject_MockObject $lockableCommand */
        $lockableCommand = $this
            ->getMockBuilder(TestableLockableCommand::class)
            ->getMock();

        $lockableCommand
            ->expects($this->atLeastOnce())
            ->method('getLockTimeToLiveInSeconds')
            ->willReturn(20);

        return $lockableCommand;
    }

    /**
     * @return TestableLockableCommand|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getCommandMockForTerminate()
    {
        /** @var TestableLockableCommand|\PHPUnit_Framework_MockObject_MockObject $lockableCommand */
        $lockableCommand = $this
            ->getMockBuilder(TestableLockableCommand::class)
            ->getMock();

        $lockableCommand
            ->expects($this->never())
            ->method('getLockTimeToLiveInSeconds');

        return $lockableCommand;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ConsoleOutput
     */
    private function getOutputInterfaceMock()
    {
        /** @var ConsoleOutput|\PHPUnit_Framework_MockObject_MockObject $outputInterface */
        $outputInterface = $this
            ->getMockBuilder(ConsoleOutput::class)
            ->getMock();

        $outputInterface
            ->expects($this->once())
            ->method('writeln');

        return $outputInterface;
    }
}


class TestableLockableCommand implements LockableCommandInterface
{
    public function getLockTimeToLiveInSeconds()
    {
        return 20;
    }
}