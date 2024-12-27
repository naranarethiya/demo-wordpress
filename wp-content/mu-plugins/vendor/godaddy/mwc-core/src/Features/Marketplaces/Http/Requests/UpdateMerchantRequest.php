<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Http\Requests;

/**
 * API request to update a GDM merchant.
 */
class UpdateMerchantRequest extends ProvisionMerchantRequest
{
    /**
     * {@inheritDoc}
     */
    public function __construct()
    {
        $this->route = 'merchants/'.$this->getMerchantAccountIdentifier();

        parent::__construct();

        $this->setMethod('PUT');
    }
}
