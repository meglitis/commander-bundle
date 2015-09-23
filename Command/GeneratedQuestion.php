<?php


namespace Guscware\CommanderBundle\Command;


use Symfony\Component\Console\Question\Question;
use Symfony\Component\Validator\Validator;

class GeneratedQuestion
{
    const OPT_DEFAULT_VALUE              = "default";
    const OPT_REQUIRED                   = "required";
    const OPT_REUSE_VARIABLE_FOR_DEFAULT = "reuse_variable_for_default";

    /** @var array */
    static private $allowedOptions = [
        self::OPT_DEFAULT_VALUE,
        self::OPT_REQUIRED,
    ];

    /** @var String */
    protected $question;
    /** @var mixed */
    protected $defaultValue;
    /** @var boolean */
    protected $required;
    /** @var string */
    protected $reuseVariableName;

    /**
     * TODO: rework $options into an object OptionsResolver or something
     *
     * @param string $question
     * @param array  $options
     */
    public function __construct($question, $options = [])
    {
        $this->question = $question;
        $this->parseOptions($options);
    }

    /**
     * Prettifies the question and returns it
     *
     * @return Question
     */
    public function getQuestion()
    {
        $questionString = $this->question;
        $questionString = "<info>$questionString</info>";
        if ($this->getDefaultValue()) {
            $questionString .= sprintf(" <comment>(%s)</comment>", $this->getDefaultValue());
        }
        $questionString .= ": ";
        $modifiedQuestion = new Question($questionString, $this->getDefaultValue());

        return $modifiedQuestion;
    }

    /**
     * @return mixed|null
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * Runs the $this::processReusedVariable() on the $defaultValue before setting it
     *
     * @param $defaultValue
     *
     * @return $this
     */
    final public function processAndSetDefaultValue($defaultValue)
    {
        $this->defaultValue = $this->processReusedVariable($defaultValue);

        return $this;
    }

    /**
     * @return boolean
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * @return string|null
     */
    public function getReuseVariableName()
    {
        return $this->reuseVariableName;
    }

    /**
     * Post processes an answer, if any transformations must be made.
     *
     * @param string $answer
     *
     * @return string
     */
    public function postProcessAnswer($answer)
    {
        return $answer;
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
        return $value;
    }


    /**
     * @param array $options
     */
    private function parseOptions(array $options)
    {
        if (isset($options[self::OPT_DEFAULT_VALUE])) {
            $this->defaultValue = $options[self::OPT_DEFAULT_VALUE];
        }

        if (in_array(self::OPT_REQUIRED, $options)) {
            $this->required = true;
        } else {
            if (isset($options[self::OPT_REQUIRED])) {
                $this->required = (bool)$options[self::OPT_REQUIRED];
            }
        }

        if (isset($options[self::OPT_REUSE_VARIABLE_FOR_DEFAULT])) {
            $this->reuseVariableName = $options[self::OPT_REUSE_VARIABLE_FOR_DEFAULT];
        }
    }
}