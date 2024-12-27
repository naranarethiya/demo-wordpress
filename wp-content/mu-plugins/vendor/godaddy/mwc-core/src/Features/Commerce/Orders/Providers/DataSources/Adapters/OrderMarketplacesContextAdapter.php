<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\Adapters;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\OrderContext;

class OrderMarketplacesContextAdapter extends OrderContextAdapter
{
    public const ORDER_CONTEXT_OWNER = 'urn:com.marketplaces:commerce.order';

    /**
     * {@inheritDoc}
     */
    public function convertToSource($target) : OrderContext
    {
        $orderContext = parent::convertToSource($target);

        if ($channelId = $target->getOriginatingChannelId()) {
            $orderContext->channelId = $channelId;
        }

        return $orderContext;
    }
}
