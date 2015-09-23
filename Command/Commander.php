<?php
/**
 * Created by PhpStorm.
 * User: gustavs
 * Date: 23/03/15
 * Time: 14:08
 */

namespace Guscware\CommanderBundle\Command;

use Guscware\CommanderBundle\Command\GeneratedQuestion\BooleanQuestion;
use Guscware\CommanderBundle\Exceptions\CommanderException;
use Guscware\CommanderBundle\Exceptions\GeneratedQuestion\GeneratedQuestionException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Commander extends Command
{
    const VERBOSITY_STRATEGY_AT_AND_ABOVE = 1;
    const VERBOSITY_STRATEGY_EXACT        = 0;
    const VERBOSITY_STRATEGY_AT_AND_BELOW = -1;

    const VERBOSITY_LEVEL_ALL          = -1;
    const VERBOSITY_LEVEL_QUIET        = OutputInterface::VERBOSITY_QUIET;
    const VERBOSITY_LEVEL_NORMAL       = OutputInterface::VERBOSITY_NORMAL;
    const VERBOSITY_LEVEL_VERBOSE      = OutputInterface::VERBOSITY_VERBOSE;
    const VERBOSITY_LEVEL_VERY_VERBOSE = OutputInterface::VERBOSITY_VERY_VERBOSE;
    const VERBOSITY_LEVEL_DEBUG        = OutputInterface::VERBOSITY_DEBUG;

    const OFFSET_LEGEND  = 2;
    const OFFSET_SUMMARY = 0;

    const SEPARATOR_LENGTH = 80;
    const SEPARATOR_SYMBOL = "=";

    /** @var int $verbosityLevel */
    private $verbosityLevel;

    /** @var InputInterface $inputInterface */
    private $inputInterface;

    /** @var OutputInterface $outputInterface */
    private $outputInterface;

    /**
     * @return InputInterface
     */
    public function getInputInterface()
    {
        return $this->inputInterface;
    }

    /**
     * @return OutputInterface
     */
    public function getOutputInterface()
    {
        return $this->outputInterface;
    }

    /**
     * Adding custom styles and helper functions here
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->inputInterface  = $input;
        $this->outputInterface = $output;

        $isQuietMode    = (bool)$input->getOption("quiet");
        $inputVerbosity = (int)$input->getOption("verbose");

        if ($isQuietMode) {
            $verbosityLevel = self::VERBOSITY_LEVEL_QUIET;
        } else {
            $verbosityLevel = $inputVerbosity > $output->getVerbosity() ? $inputVerbosity : $output->getVerbosity();
        }

        $this->verbosityLevel = $verbosityLevel;

        $this->initializeCustomStyles();
        parent::initialize($input, $output);
    }

    /**
     * Write to output, if the current verbosity level is at or above the specified $verbosity argument
     * If the --quiet flag is specified - there will be no output
     *
     * @param string $string
     * @param int    $verbosityLevel
     * @param int    $verbosityStrategy
     *                  - {at and above}: this verbosity + more verbose levels
     *                  - {exact}: only in this verbosity level
     *                  - {at and below}: this verbosity + less verbose levels
     *
     * @throws CommanderException
     */
    public function write(
        $string = '',
        $verbosityLevel = self::VERBOSITY_LEVEL_NORMAL,
        $verbosityStrategy = self::VERBOSITY_STRATEGY_AT_AND_ABOVE
    ) {
        if ($this->verbosityLevel <= self::VERBOSITY_LEVEL_QUIET) {
            return;
        }

        $canOutput =
            ($verbosityStrategy === self::VERBOSITY_STRATEGY_AT_AND_ABOVE && $this->verbosityLevel >= $verbosityLevel)
            || ($verbosityStrategy === self::VERBOSITY_STRATEGY_EXACT && $this->verbosityLevel === $verbosityLevel)
            || ($verbosityStrategy === self::VERBOSITY_STRATEGY_AT_AND_BELOW && $this->verbosityLevel <= $verbosityLevel);

        if ($canOutput) {
            $this->writeToOutput($string);
        }
    }

    /**
     * Write a line to output, if the current verbosity level is at or above the specified $verbosity argument
     * If the --quiet flag is specified - there will be no output
     *
     * @param string $string
     * @param int    $verbosityLevel
     * @param int    $verbosityStrategy
     *                  - {at and above}: this verbosity + more verbose levels
     *                  - {exact}: only in this verbosity level
     *                  - {at and below}: this verbosity + less verbose levels
     *
     * @throws CommanderException
     */
    public function writeLine(
        $string = '',
        $verbosityLevel = self::VERBOSITY_LEVEL_NORMAL,
        $verbosityStrategy = self::VERBOSITY_STRATEGY_AT_AND_ABOVE
    ) {
        $string .= PHP_EOL;
        $this->write($string, $verbosityLevel, $verbosityStrategy);
    }

    /**
     * Gets a separator string, with an optional text in the middle
     *
     * @param string $inlineText
     *
     * @return string
     */
    public function writeSeparatorLine($inlineText = null)
    {
        $padLength = self::SEPARATOR_LENGTH;
        if (!is_null($inlineText)) {
            $inlineText = " <comment>" . $inlineText . "</comment> ";
            $padLength += strlen("<comment></comment>");
        }


        $paddedString = str_pad(
            $inlineText,
            $padLength,
            self::SEPARATOR_SYMBOL,
            STR_PAD_BOTH
        );

        $this->writeLine("<info>" . $paddedString . "</info>");
    }


    /**
     * @param string $message
     */
    protected function writeNotification($message)
    {
        $this->writeLine(
            sprintf(
                "  <cyan>-> $message</cyan>"
            )
        );
    }


    /**
     * Outputs the title of this command, if the verbosity level is at leat NORMAL
     *
     * @param $title
     */
    protected function outputCommandTitle($title)
    {
        $this->writeLine();
        $this->writeSeparatorLine($title);
    }

    /**
     * Receives an associative array of [title => value, ..]
     * And outputs it to screen on verbosity at least NORMAL
     *
     * @param $data
     */
    protected function outputSummaryFromArray($data)
    {
        if (empty($data)) {
            return;
        }

        $maxKeyLength = $this->getMaxStringLengthInArrayKeys($data) + self::OFFSET_SUMMARY;

        $this->writeLine();

        // Iterate through all keys and their values - format and write to the output
        foreach ($data as $key => $value) {
            $this->writeLine(
                sprintf(
                    "%s: %s",
                    $this->padConsoleString($key, $maxKeyLength),
                    $value
                )
            );
        }

        $this->writeLine();
    }


    /**
     * TODO: if there are any tags in the middle of the $string, they won't be properly placed back
     *
     * Pads a string by stripping all tags first, padding it, then re-applying tags
     *
     * @param $string
     * @param $padLength
     *
     * @return mixed
     */
    private function padConsoleString($string, $padLength)
    {
        $stringWithNoTags       = strip_tags($string);
        $paddedStringWithNoTags = str_pad($stringWithNoTags, $padLength);
        $paddedString           = str_replace($stringWithNoTags, $string, $paddedStringWithNoTags);

        return $paddedString;
    }

    /**
     * Iterates through all keys in $array, strips their tags, and compares their length
     * Returns the longest no-tag key length
     *
     * @param array $array
     *
     * @return int|null
     */
    private function getMaxStringLengthInArrayKeys($array)
    {
        // List all keys
        $keys         = array_keys($array);
        $maxKeyLength = 0;

        // Remove all tags, if there are any
        array_walk(
            $keys,
            function (&$value, $key) use ($maxKeyLength) {
                $value = strip_tags($value);
                max($maxKeyLength, strlen($value));
            }
        );

        return $maxKeyLength;
    }


    /**
     * Sets custom styles for console output
     */
    private function initializeCustomStyles()
    {
        $red     = new OutputFormatterStyle('red', 'black');
        $cyan    = new OutputFormatterStyle('cyan', 'black');
        $magenta = new OutputFormatterStyle('magenta', 'black');

        $this->outputInterface->getFormatter()->setStyle('red', $red);
        $this->outputInterface->getFormatter()->setStyle('cyan', $cyan);
        $this->outputInterface->getFormatter()->setStyle('magenta', $magenta);
    }

    /**
     * Write $string to OutputInterface using ::write()
     *
     * @param string $string
     */
    private function writeToOutput($string)
    {
        $this->outputInterface->write($string);
    }

    /**
     * TODO: move this over to QuestionParser or something along those lines
     *
     * @param GeneratedQuestion[] $questionStack
     * @param bool|false          $includeValueConfirmation - if true, the user will get an overview of entered
     *                                                      values and a confirmation box asking if they are correct
     *
     * @return array -> [key => value] pairs of question variableNames and their user provided values
     *
     * @throws CommanderException
     */
    final protected function executeQuestionStack(array $questionStack, $includeValueConfirmation = false)
    {
        if (empty($questionStack)) {
            throw new CommanderException("Cannot execute questions. The question stack is empty.");
        }

        $helper       = $this->getHelper("question");
        $input        = $this->inputInterface;
        $output       = $this->outputInterface;
        $returnValues = [];

        /**
         * Get the first iterator values for the loop
         *
         * @var string            $variableName
         * @var GeneratedQuestion $questionItem
         */
        list($variableName, $questionItem) = each($questionStack);


        // Do-While is the right choice here, since we have to run indefinitely
        // Until a user enters a value, if it's set to Required
        do {
            $reuseVariableName = $questionItem->getReuseVariableName();
            if (!is_null($reuseVariableName)) {
                if (!isset($returnValues[$reuseVariableName])) {
                    throw new CommanderException(
                        sprintf(
                            "Cannot reuse variable [%s] because it hasn't been found in the answer stack",
                            $reuseVariableName
                        )
                    );
                }
                $questionItem->processAndSetDefaultValue($returnValues[$reuseVariableName]);
            }

            $userValue = $helper->ask($input, $output, $questionItem->getQuestion());

            if (empty($userValue) && $questionItem->isRequired()) {
                $this->writeLine("<cyan>-> This is a required field. Please enter a valid value.</cyan>");
                continue;
            }

            try {
                // Do any modifications to the answer, if any have to be done
                $userValue = $questionItem->postProcessAnswer($userValue);
            } catch (GeneratedQuestionException $e) {
                $this->writeLine("  <red>-> {$e->getMessage()}</red>");
                continue;
            }

            $returnValues[$variableName] = $userValue;


            // Get next values. If they're empty - exit loop
            $nextValues = each($questionStack);
            if ($nextValues === false) {
                break;
            }

            list($variableName, $questionItem) = $nextValues;
        } while (true);


        if ($includeValueConfirmation) {
            $this->writeLine($this->writeSeparatorLine("Confirm values"));
            $this->outputSummaryFromArray($returnValues);

            $this->confirmOrExit("Is this information correct?");

            $this->writeLine($this->writeSeparatorLine());
        }

        return $returnValues;
    }

    /**
     * Outputs the $confirmationMessage and depending on the user answer
     * and returns the chosen answer
     *
     * @param string $confirmationMessage
     * @param bool   $defaultValue
     *
     * @return bool
     */
    protected function confirm($confirmationMessage, $defaultValue = false)
    {
        $helper = $this->getHelper("question");
        $input  = $this->getInputInterface();
        $output = $this->getOutputInterface();

        $confirmChangesQuestion = new BooleanQuestion(
            $confirmationMessage,
            [GeneratedQuestion::OPT_DEFAULT_VALUE => $defaultValue]
        );

        $confirmationValue = $helper->ask($input, $output, $confirmChangesQuestion->getQuestion());

        return $confirmationValue;
    }

    /**
     * Outputs the $confirmationMessage and depending on the user answer
     * Either continues or exits the program
     *
     * @param string     $confirmationMessage
     * @param bool|false $defaultValue
     */
    protected function confirmOrExit($confirmationMessage, $defaultValue = false)
    {
        $areChangesConfirmed = $this->confirm($confirmationMessage, $defaultValue);

        if (!$areChangesConfirmed) {
            $this->writeNotification("User cancelled command. Exiting.");
            exit;
        }
    }
}