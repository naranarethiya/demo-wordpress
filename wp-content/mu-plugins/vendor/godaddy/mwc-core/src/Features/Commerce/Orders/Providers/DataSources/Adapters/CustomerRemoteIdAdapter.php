<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\Adapters;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\DataSources\Builders\OrderToCustomerBuilder;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Customers\Services\Contracts\CustomersMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\Contracts\DataObjectAdapterContract;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;

class CustomerRemoteIdAdapter implements DataObjectAdapterContract
{
    protected OrderToCustomerBuilder $orderToCustomerBuilder;

    protected CustomersMappingServiceContract $customersMappingService;

    public function __construct(
        OrderToCustomerBuilder $orderToCustomerBuilder,
        CustomersMappingServiceContract $customersMappingService
    ) {
        $this->orderToCustomerBuilder = $orderToCustomerBuilder;
        $this->customersMappingService = $customersMappingService;
    }

    /**
     * Converts a remote customer ID into a local customer ID.
     *
     * @param string|null $source
     * @return int|null
     */
    public function convertFromSource($source) : ?int
    {
        // No-op for now
        return null;
    }

    /**
     * Converts an Order's customer ID into a remote customer ID.
     *
     * @param Order $target
     * @return non-empty-string|null
     */
    public function convertToSource($target) : ?string
    {
        if (! $customer = $this->orderToCustomerBuilder->setOrder($target)->build()) {
            return null;
        }

        return $this->customersMappingService->getRemoteId($customer) ?: null;
    }
}
