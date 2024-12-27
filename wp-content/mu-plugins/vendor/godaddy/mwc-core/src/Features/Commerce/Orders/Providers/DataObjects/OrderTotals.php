<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\SimpleMoney;

class OrderTotals extends AbstractDataObject
{
    /** @var SimpleMoney discount total amount. Always non-positive (<=0) */
    public SimpleMoney $discountTotal;

    /** @var SimpleMoney fees total amount */
    public SimpleMoney $feeTotal;

    /** @var SimpleMoney shipping total amount */
    public SimpleMoney $shippingTotal;

    /** @var SimpleMoney subtotal amount */
    public SimpleMoney $subTotal;

    /** @var SimpleMoney tax total amount */
    public SimpleMoney $taxTotal;

    /** @var SimpleMoney total amount */
    public SimpleMoney $total;

    /**
     * Creates a order's line item totals data object.
     *
     * @param array{
     *     discountTotal: SimpleMoney,
     *     feeTotal: SimpleMoney,
     *     shippingTotal: SimpleMoney,
     *     subTotal: SimpleMoney,
     *     taxTotal: SimpleMoney,
     *     total: SimpleMoney
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
