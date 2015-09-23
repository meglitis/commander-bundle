<?php

namespace Guscware\CommanderBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestCommand extends Commander implements LockableCommandInterface
{
    public function getLockTimeToLiveInSeconds()
    {
        return 20;
    }

    protected function configure()
    {
        $this->setName('test:command');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        throw new \Exception('Test');
    }
}