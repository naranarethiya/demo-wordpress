<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Stripe\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe\Models\Account;

/**
 * An adapter for handling stripe account data.
 */
class AccountAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var Account account */
    protected $source;

    /**
     * Constructor.
     *
     * @param Account $account
     */
    public function __construct(Account $account)
    {
        $this->source = $account;
    }

    /**
     * {@inheritDoc}
     */
    public function convertFromSource()
    {
        // Not implemented.
        return [];
    }

    /**
     * Converts account data from Stripe to source structure.
     *
     * @param array|null $data
     * @return Account
     */
    public function convertToSource(array $data = null) : Account
    {
        if ($id = ArrayHelper::get($data, 'id', null)) {
            $this->source->setId(TypeHelper::string($id, ''));
        }

        if ($email = ArrayHelper::get($data, 'email', null)) {
            $this->source->setEmailAddress(TypeHelper::string($email, ''));
        }

        if ($capabilities = ArrayHelper::get($data, 'capabilities', [])) {
            $this->source->setCapabilities(array_keys(TypeHelper::array($capabilities, []), 'active'));
        }

        return $this->source;
    }
}
