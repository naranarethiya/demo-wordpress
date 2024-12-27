<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\Adapters;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Contracts\HasStoreIdentifierContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\OrderContext;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\Contracts\DataObjectAdapterContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\HasStoreIdentifierTrait;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;

class OrderContextAdapter implements DataObjectAdapterContract, HasStoreIdentifierContract
{
    use HasStoreIdentifierTrait;

    public const ORDER_CONTEXT_OWNER = 'urn:com.godaddy.mwcstores:commerce.order';

    public string $defaultChannelId = '';

    public function __construct(string $defaultChannelId)
    {
        $this->defaultChannelId = $defaultChannelId;
    }

    /**
     * Converts a Commerce's order context.
     *
     * @param OrderContext $source
     */
    public function convertFromSource($source) : void
    {
        // No-op
    }

    /**
     * Converts a line item into a Commerce's order context object.
     *
     * @param Order $target
     * @return OrderContext
     */
    public function convertToSource($target) : OrderContext
    {
        return new OrderContext([
            'channelId' => $this->defaultChannelId,
            'owner'     => static::ORDER_CONTEXT_OWNER,
            'storeId'   => $this->getStoreId(),
        ]);
    }
}
