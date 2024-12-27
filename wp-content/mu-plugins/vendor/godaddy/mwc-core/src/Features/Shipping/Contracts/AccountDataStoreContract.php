<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Shipping\Contracts;

use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\AccountContract;

interface AccountDataStoreContract
{
    /**
     * Reads an account from the data store.
     *
     * @param string|null $identifier
     * @return AccountContract
     */
    public function read(?string $identifier = null) : AccountContract;

    /**
     * Saves an account to the data store.
     *
     * @param AccountContract $account
     * @return AccountContract
     */
    public function save(AccountContract $account) : AccountContract;

    /**
     * Deletes an account from the data store.
     *
     * @param AccountContract $account
     * @return AccountContract
     */
    public function delete(AccountContract $account) : AccountContract;
}
