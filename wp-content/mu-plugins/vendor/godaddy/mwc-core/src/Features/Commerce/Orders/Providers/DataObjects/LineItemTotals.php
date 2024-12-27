<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\SimpleMoney;

class LineItemTotals extends AbstractDataObject
{
    /** @var SimpleMoney discount total amount */
    public SimpleMoney $discountTotal;

    /** @var SimpleMoney fees total amount */
    public SimpleMoney $feeTotal;

    /** @var SimpleMoney subtotal amount */
    public SimpleMoney $subTotal;

    /** @var SimpleMoney tax total amount */
    public SimpleMoney $taxTotal;

    /**
     * Creates a order's line item totals data object.
     *
     * @param array{
     *     discountTotal: SimpleMoney,
     *     feeTotal: SimpleMoney,
     *     subTotal: SimpleMoney,
     *     taxTotal: SimpleMoney
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
