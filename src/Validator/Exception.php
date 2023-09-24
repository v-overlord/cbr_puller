<?php

namespace CbrPuller\Validator;

class Exception extends \Exception
{

    /**
     * Error messages indicating invalid values from the validator.
     *
     * @var array<string>
     */
    public array $errorMessages = [];

    /**
     * @param array<string> $messages
     */
    public function __construct(array $messages)
    {
        $this->errorMessages = $messages;

        parent::__construct();
    }
}