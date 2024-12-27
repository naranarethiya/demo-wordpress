<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\Adapters\Factories;

use GoDaddy\WordPress\MWC\Common\Platforms\Contracts\PlatformRepositoryContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\OrderContext;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\Adapters\OrderContextAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\Adapters\OrderMarketplacesContextAdapter;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;

class OrderContextAdapterFactory
{
    protected PlatformRepositoryContract $platformRepository;

    public function __construct(PlatformRepositoryContract $platformRepository)
    {
        $this->platformRepository = $platformRepository;
    }

    /**
     * Gets the appropriate order context adapter to use based on the data provided in the order context.
     *
     * @param OrderContext $source
     *
     * @return OrderContextAdapter
     */
    public function getAdapterFromSource(OrderContext $source) : OrderContextAdapter
    {
        if (OrderMarketplacesContextAdapter::ORDER_CONTEXT_OWNER === $source->owner) {
            return new OrderMarketplacesContextAdapter($this->platformRepository->getChannelId());
        }

        return new OrderContextAdapter($this->platformRepository->getChannelId());
    }

    /**
     * Gets the appropriate order context adapter to use based on the data provided in the order.
     *
     * @param Order $order
     *
     * @return OrderContextAdapter
     */
    public function getAdapterFromTarget(Order $order) : OrderContextAdapter
    {
        if ($order->hasMarketplacesChannel()) {
            return new OrderMarketplacesContextAdapter($this->platformRepository->getChannelId());
        }

        return new OrderContextAdapter($this->platformRepository->getChannelId());
    }
}
