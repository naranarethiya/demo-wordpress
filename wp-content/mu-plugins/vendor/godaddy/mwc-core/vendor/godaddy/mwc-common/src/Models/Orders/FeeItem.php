<?php

namespace GoDaddy\WordPress\MWC\Common\Models\Orders;

use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;

/**
 * An representation of fee item in an Order.
 */
class FeeItem extends AbstractOrderItem
{
    /** @var CurrencyAmount total tax amount */
    protected $taxAmount;

    /**
     * Gets the tax total amount object.
     *
     * @return CurrencyAmount
     */
    public function getTaxAmount() : CurrencyAmount
    {
        return $this->taxAmount;
    }

    /**
     * Sets tax total amount object.
     *
     * @param CurrencyAmount $taxAmount
     * @return $this
     */
    public function setTaxAmount(CurrencyAmount $taxAmount) : FeeItem
    {
        $this->taxAmount = $taxAmount;

        return $this;
    }
}
