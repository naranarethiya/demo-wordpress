<?php

namespace GoDaddy\WordPress\MWC\Common\Models\Orders;

/**
 * A representation of tax item in an Order.
 */
class TaxItem extends AbstractOrderItem
{
    /**
     * tax item's rate.
     *
     * @var float
     */
    protected $rate;

    /**
     * Gets tax item rate.
     *
     * @return float
     */
    public function getRate() : float
    {
        return $this->rate;
    }

    /**
     * Sets tax item rate.
     *
     * @param float $rate
     * @return $this
     */
    public function setRate(float $rate) : TaxItem
    {
        $this->rate = $rate;

        return $this;
    }
}
