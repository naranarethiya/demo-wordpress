<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Payloads;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\PayloadContract;

class EmptyPayload implements PayloadContract
{
    /**
     * {@inheritDoc}
     */
    public function hasValue() : bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     * @return null
     */
    public function getValue()
    {
        return null;
    }
}
