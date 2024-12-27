<?php

namespace GoDaddy\WordPress\MWC\Shipping\Models\Contracts;

use GoDaddy\WordPress\MWC\Common\Contracts\HasLabelContract;
use GoDaddy\WordPress\MWC\Common\Models\Contracts\ModelContract;
use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;

interface ShippingRateItemContract extends ModelContract, HasLabelContract
{
    /**
     * Gets the price.
     *
     * @return CurrencyAmount
     */
    public function getPrice() : CurrencyAmount;

    /**
     * Sets the price.
     *
     * @param CurrencyAmount $value
     *
     * @return $this
     */
    public function setPrice(CurrencyAmount $value);

    /**
     * Gets the is included flag.
     *
     * @return bool
     */
    public function getIsIncluded() : bool;

    /**
     * Sets the is included flag.
     *
     * @return $this
     */
    public function setIsIncluded(bool $value);
}
