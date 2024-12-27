<?php

namespace GoDaddy\WordPress\MWC\Shipping\Contracts;

use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\AccountContract;

interface CanDisconnectShippingAccountContract
{
    /**
     * Disconnects from given shipping account.
     *
     * @param AccountContract $account
     * @return AccountContract
     */
    public function disconnect(AccountContract $account) : AccountContract;
}
