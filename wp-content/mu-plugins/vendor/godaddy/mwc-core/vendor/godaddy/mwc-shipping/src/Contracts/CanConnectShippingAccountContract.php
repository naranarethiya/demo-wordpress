<?php

namespace GoDaddy\WordPress\MWC\Shipping\Contracts;

use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\AccountContract;

interface CanConnectShippingAccountContract
{
    /**
     * Connects to given shipping account.
     *
     * @param AccountContract $account
     * @return AccountContract
     */
    public function connect(AccountContract $account) : AccountContract;
}
