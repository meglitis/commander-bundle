<?php


namespace Guscware\CommanderBundle\Command\GeneratedQuestion;


use Guscware\CommanderBundle\Command\GeneratedQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class BooleanQuestion extends GeneratedQuestion
{
    /**
     * Post processes an answer, if any transformations must be made.
     *
     * @param string $answer
     *
     * @return string
     */
    public function postProcessAnswer($answer)
    {
        if (is_bool($answer)) {
            return $answer;
        } else if (is_string($answer)) {
            switch (strtolower($answer)) {
                case "0":
                case "false":
                    return false;
                    break;

                case "1":
                case "true":
                    return true;
                    break;

                default:
                    return (bool)$this->getDefaultValue();

                    break;
            }
        } else {
            return (bool) $answer;
        }
    }

    /**
     * If anything has to be done with the reused variable, before it's used
     * as the default - it must be implemented here
     *
     * @param string $value
     *
     * @return string
     */
    public function processReusedVariable($value)
    {
        return (bool) $value;
    }

    /**
     * Prettifies the question and returns it
     *
     * @return ConfirmationQuestion
     */
    public function getQuestion()
    {
        $questionString = $this->question;
        $questionString = "<info>$questionString</info>";
        $questionString .= sprintf(" <comment>(%s)</comment>", ($this->getDefaultValue() ? "Y/n" : "y/N"));
        $questionString .= ": ";
        $modifiedQuestion = new ConfirmationQuestion($questionString, $this->getDefaultValue());

        return $modifiedQuestion;
    }

}