<?php

namespace GoDaddy\WordPress\MWC\Common\Validation\Contracts;

use GoDaddy\WordPress\MWC\Common\Exceptions\ValidationException;

/**
 * Contract for classes that validate a given input against a set of rules.
 */
interface ValidatorContract
{
    /**
     * Sets validation rules.
     *
     * @param ValidationRuleContract[] $rules
     * @return $this
     */
    public function setRules(array $rules) : ValidatorContract;

    /**
     * Validates the given input against the entire array of rules.
     *
     * @param mixed $input
     * @return void
     * @throws ValidationException
     */
    public function validate($input) : void;
}
