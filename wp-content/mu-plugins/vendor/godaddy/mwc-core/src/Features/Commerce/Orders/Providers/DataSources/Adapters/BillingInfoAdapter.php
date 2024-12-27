<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\Adapters;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\BillingInfo;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\Contracts\DataObjectAdapterContract;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;

class BillingInfoAdapter implements DataObjectAdapterContract
{
    /**
     * {@inheritDoc}
     *
     * @param BillingInfo $source
     */
    public function convertFromSource($source)
    {
        // no-op for now
        // TODO: Implement convertFromSource() method.
    }

    /**
     * {@inheritDoc}
     * @param Order $target
     *
     * @return BillingInfo
     */
    public function convertToSource($target) : BillingInfo
    {
        return new BillingInfo([
            'firstName' => $target->getBillingAddress()->getFirstName(),
            'lastName'  => $target->getBillingAddress()->getLastName(),
        ]);
    }
}
