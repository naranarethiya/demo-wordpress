<?php

namespace GoDaddy\WordPress\MWC\Shipping\Traits;

use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\AccountContract;

trait HasAccountTrait
{
    /** @var AccountContract */
    protected $account;

    /**
     * {@inheritDoc}
     */
    public function getAccount() : AccountContract
    {
        return $this->account;
    }

    /**
     * {@inheritDoc}
     */
    public function setAccount(AccountContract $value)
    {
        $this->account = $value;

        return $this;
    }
}
