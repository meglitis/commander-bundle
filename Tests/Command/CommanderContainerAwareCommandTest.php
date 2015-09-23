<?php

namespace Command;

use Guscware\CommanderBundle\Command\Commander;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

class CommanderTest extends \PHPUnit_Framework_TestCase
{
    /** @var CommandTester $commandTester */
    private $commandTester;
    /** @var Command $command */
    private $command;

    protected function setUp()
    {
        $application = new Application();
        $application->add(new CommanderFunctionalityTestCommand());

        $command       = $application->find("commander:test");
        $this->command = $command;
        $commandTester = new CommandTester($command);

        $this->commandTester = $commandTester;
    }

    public function testExecute()
    {
        $this->executeCommandTester();
        $this->assertRegExp('/= Commander online =/', $this->commandTester->getDisplay());
    }

    public function testSummaryOutput()
    {
        $this->executeCommandTester();
        $this->assertRegExp('/summary: item/', $this->commandTester->getDisplay());
    }

    /** verbosity = QUIET */
    public function testQuietVerbosity()
    {
        $this->executeCommandTester(["--quiet" => true]);
        $output = $this->commandTester->getDisplay();

        $this->assertEmpty($output, $output);
    }

    /** verbosity = NORMAL */
    public function testNormalVerbosity()
    {
        $this->executeCommandTester(["--verbose" => Commander::VERBOSITY_LEVEL_NORMAL]);
        $output = $this->commandTester->getDisplay();

        $contains    = [
            'normal only',
            'normal and up',
            'normal and below',
            'verbose and below',
            'very-verbose and below',
        ];
        $notContains = [
            'verbose only',
            'very-verbose only',
            'verbose and up',
            'very-verbose and up',
        ];

        foreach ($contains as $string) {
            $this->assertRegExp('/\b' . $string . '\n/', $output);
        }

        foreach ($notContains as $string) {
            $this->assertNotRegExp('/\b^' . $string . '\n/', $output);
        }
    }

    /** verbosity = VERBOSE */
    public function testVerboseVerbosity()
    {
        $this->executeCommandTester(["--verbose" => Commander::VERBOSITY_LEVEL_VERBOSE]);
        $output = $this->commandTester->getDisplay();

        $contains    = [
            'verbose only',
            'normal and up',
            'verbose and up',
            'very-verbose and below',
            'verbose and below',
        ];
        $notContains = [
            'normal only',
            'very-verbose only',
            'very-verbose and up',
            'normal and below',
        ];

        foreach ($contains as $string) {
            $this->assertRegExp('/\b' . $string . '\n/', $output);
        }

        foreach ($notContains as $string) {
            $this->assertNotRegExp('/\b^' . $string . '\n/', $output);
        }
    }

    /** verbosity = VERY_VERBOSE */
    public function testVeryVerboseVerbosity()
    {
        $this->executeCommandTester(["--verbose" => Commander::VERBOSITY_LEVEL_VERY_VERBOSE]);
        $output = $this->commandTester->getDisplay();

        $contains    = [
            'normal and up',
            'verbose and up',
            'very-verbose only',
            'very-verbose and up',
            'very-verbose and below',
        ];
        $notContains = [
            'normal only',
            'verbose only',
            'normal and below',
            'verbose and below',
        ];

        foreach ($contains as $string) {
            $this->assertRegExp('/\b' . $string . '\n/', $output);
        }

        foreach ($notContains as $string) {
            $this->assertNotRegExp('/\b^' . $string . '\n/', $output);
        }
    }

    /**
     * Executes the CommandTester with command name by default
     * and allows specific arguments to be passed
     *
     * @param array $additionalArgs
     */
    private function executeCommandTester($additionalArgs = [])
    {
        $defaultCommandArgs = [
            "command" => $this->command->getName(),
        ];

        $cmdArgs = array_merge($additionalArgs, $defaultCommandArgs);
        $this->commandTester->execute($cmdArgs);
    }
}

final class CommanderFunctionalityTestCommand extends Commander
{
    protected function configure()
    {
        $this
            ->setName("commander:test")
            ->setDescription("A dedicated command for testing Commander functionality");
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->outputCommandTitle("Commander online");
        $this->outputSummaryFromArray(
            [
                "summary" => "item",
            ]
        );

        // Output only for verbose
        $this->writeLine(
            "normal only",
            Commander::VERBOSITY_LEVEL_NORMAL,
            Commander::VERBOSITY_STRATEGY_EXACT
        );
        $this->writeLine(
            "verbose only",
            Commander::VERBOSITY_LEVEL_VERBOSE,
            Commander::VERBOSITY_STRATEGY_EXACT
        );
        $this->writeLine(
            "very-verbose only",
            Commander::VERBOSITY_LEVEL_VERY_VERBOSE,
            Commander::VERBOSITY_STRATEGY_EXACT
        );

        $this->writeLine("normal and up");
        $this->writeLine(
            "verbose and up",
            Commander::VERBOSITY_LEVEL_VERBOSE
        );
        $this->writeLine(
            "very-verbose and up",
            Commander::VERBOSITY_LEVEL_VERY_VERBOSE
        );

        $this->writeLine(
            "very-verbose and below",
            Commander::VERBOSITY_LEVEL_VERY_VERBOSE,
            Commander::VERBOSITY_STRATEGY_AT_AND_BELOW
        );
        $this->writeLine(
            "verbose and below",
            Commander::VERBOSITY_LEVEL_VERBOSE,
            Commander::VERBOSITY_STRATEGY_AT_AND_BELOW
        );
        $this->writeLine(
            "normal and below",
            Commander::VERBOSITY_LEVEL_NORMAL,
            Commander::VERBOSITY_STRATEGY_AT_AND_BELOW
        );
    }
}