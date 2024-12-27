<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts;

use Throwable;

interface CommerceExceptionContract extends Throwable
{
    /**
     * Gets sync error code.
     *
     * @return string
     */
    public function getErrorCode() : string;
}
