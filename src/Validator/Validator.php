<?php

namespace CbrPuller\Validator;

use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Currency;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class Validator
{
    private ValidatorInterface $validationInstance;

    public function __construct()
    {
        $this->validationInstance = Validation::createValidator();
    }

    /**
     * @throws Exception
     */
    public function validate(array $values): true
    {
        $errors = [];
        $rules = $this->getRules();

        foreach ($values as $rule_name => $value) {
            if (isset($rules[$rule_name]) && is_array($rules[$rule_name])) {
                $violations = $this->validationInstance->validate($value, $rules[$rule_name]);

                if (0 !== count($violations)) {
                    $errors_ = [];
                    foreach ($violations as $violation) {
                        $errors_[] = $violation->getMessage();
                    }

                    $errors[$rule_name] = $errors_;
                }
            }
        }

        if (0 !== count($errors)) {
            throw new Exception($errors);
        }

        return true;
    }

    // @TODO: Perhaps it would be beneficial to remove the rules from the confines of the class,.. but it is not this day!
    private function getRules(): array
    {
        return [
            'currency' => [
                new Currency()
            ],
            'date' => [
                new Date()
            ],
            'base-currency' => [
                new Currency()
            ],
            'renderer' => [
                new Choice([
                    'choices' => [
                        'cli',
                        'json',
                        ],
                    'message' => 'Select the renderer that is available, either \'cli\' or \'json\'.'
                ])
            ]
        ];
    }
}