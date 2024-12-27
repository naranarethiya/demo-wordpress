<?php

namespace GoDaddy\WordPress\MWC\Common\Validation\Contracts;

/**
 * Contract for validation rules.
 */
interface ValidationRuleContract
{
    /**
     * Determines if the provided input passes validation.
     *
     * In case of failure, classes implementing this interface may opt for throwing a specific exception or returning false.
     *
     * @param mixed $input
     * @return bool
     */
    public function passes($input) : bool;
}
