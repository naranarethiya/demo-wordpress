<?php

namespace GoDaddy\WordPress\MWC\Common\Models\Orders;

use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;

/**
 * A representation of shipping item in an Order.
 */
class ShippingItem extends AbstractOrderItem
{
    /**
     * shipping item's total tax amount.
     *
     * @var CurrencyAmount
     */
    protected $taxAmount;

    /**
     * Gets shipping item tax total amount object.
     *
     * @return CurrencyAmount
     */
    public function getTaxAmount() : CurrencyAmount
    {
        return $this->taxAmount;
    }

    /**
     * Sets shipping item tax total amount object.
     *
     * @param CurrencyAmount $taxAmount
     * @return $this
     */
    public function setTaxAmount(CurrencyAmount $taxAmount) : ShippingItem
    {
        $this->taxAmount = $taxAmount;

        return $this;
    }
}
