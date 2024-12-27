<?php

namespace GoDaddy\WordPress\MWC\Shipping\Contracts;

use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\AccountContract;

interface HasAccountContract extends OperationContract
{
    /**
     * Gets the account associated with this operation.
     *
     * @return AccountContract
     */
    public function getAccount() : AccountContract;

    /**
     * Sets the account associated with this operation.
     *
     * @param AccountContract $value
     * @return $this
     */
    public function setAccount(AccountContract $value);
}
