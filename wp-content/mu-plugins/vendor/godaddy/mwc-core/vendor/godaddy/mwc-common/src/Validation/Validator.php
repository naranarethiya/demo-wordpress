<?php

namespace GoDaddy\WordPress\MWC\Common\Validation;

use GoDaddy\WordPress\MWC\Common\Exceptions\ValidationException;
use GoDaddy\WordPress\MWC\Common\Validation\Contracts\ValidationRuleContract;
use GoDaddy\WordPress\MWC\Common\Validation\Contracts\ValidatorContract;

/**
 * Class for handling input validation based on validation rules.
 */
class Validator implements ValidatorContract
{
    /** @var ValidationRuleContract[] */
    protected $rules;

    /**
     * {@inheritDoc}
     */
    public function setRules(array $rules) : ValidatorContract
    {
        $this->rules = $rules;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function validate($input) : void
    {
        foreach ($this->rules as $rule) {
            if (! $rule->passes($input)) {
                $ruleClass = get_class($rule);
                throw new ValidationException("Validation failed for rule: {$ruleClass}");
            }
        }
    }
}
