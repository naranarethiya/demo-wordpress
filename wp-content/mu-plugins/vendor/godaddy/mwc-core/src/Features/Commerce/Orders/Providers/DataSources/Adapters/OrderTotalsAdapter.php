<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\Adapters;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\OrderTotals;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\Contracts\DataObjectAdapterContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataSources\Adapters\SimpleMoneyAdapter;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;

class OrderTotalsAdapter implements DataObjectAdapterContract
{
    public function convertFromSource($source)
    {
        // TODO: Implement convertFromSource() method.
    }

    /**
     * Converts to Data Source format.
     *
     * @param Order $target The item to convert. Intended to be an Order object.
     *
     * @return OrderTotals
     */
    public function convertToSource($target) : OrderTotals
    {
        $adapter = new SimpleMoneyAdapter();

        return new OrderTotals([
            'discountTotal' => $adapter->convertToSourceOrZero($target->getDiscountAmount()),
            'feeTotal'      => $adapter->convertToSourceOrZero($target->getFeeAmount()),
            'shippingTotal' => $adapter->convertToSourceOrZero($target->getShippingAmount()),
            'subTotal'      => $adapter->convertToSourceOrZero($target->getLineAmount()),
            'taxTotal'      => $adapter->convertToSourceOrZero($target->getTaxAmount()),
            'total'         => $adapter->convertToSourceOrZero($target->getTotalAmount()),
        ]);
    }
}
