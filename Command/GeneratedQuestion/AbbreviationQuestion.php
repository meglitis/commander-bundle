<?php


namespace Guscware\CommanderBundle\Command\GeneratedQuestion;


use Guscware\CommanderBundle\Command\GeneratedQuestion;
use Guscware\CommanderBundle\Exceptions\GeneratedQuestion\QuestionAnswerInvalidException;

class AbbreviationQuestion extends GeneratedQuestion
{
    /** @var int */
    private $maxLength = 2;

    /**
     * Throw an exception if the value isn't as long as it should be
     *
     * @param string $answer
     *
     * @return string
     *
     * @throws QuestionAnswerInvalidException
     */
    public function postProcessAnswer($answer)
    {
        $answerLength = strlen($answer);
        if ($answerLength !== $this->maxLength) {
            throw new QuestionAnswerInvalidException(
                sprintf(
                    "Answer length (%d) exceeds maximum length (%d)",
                    $answerLength,
                    $this->maxLength
                )
            );
        }

        return $answer;
    }

    /**
     * We generate a suggested abbreviation from the initial and middle letters
     * of given $value
     *
     * @param string $value
     *
     * @return string
     */
    public function processReusedVariable($value)
    {
        $initialLetter = substr($value, 0, 1);
        $middleLetter = substr(
            $value,
            floor(strlen($value) / 2),
            1
        );

        $suggestedAbbreviation = strtoupper(
            $initialLetter . $middleLetter
        );

        return $suggestedAbbreviation;
    }

}