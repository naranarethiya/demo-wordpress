<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits;

trait IsCommerceExceptionTrait
{
    /**
     * Returns the exception error code.
     *
     * @return string
     */
    public function getErrorCode() : string
    {
        return $this->errorCode;
    }
}
