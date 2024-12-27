<?php

namespace GoDaddy\WordPress\MWC\Shipping\Models;

use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;
use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;
use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;
use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\ShippingRateItemContract;

/**
 * Represents an item of a shipping rate object.
 *
 * @since 0.1.0
 */
class ShippingRateItem extends AbstractModel implements ShippingRateItemContract
{
    use HasLabelTrait;

    /** @var CurrencyAmount the item price */
    protected $price;

    /** @var bool whether the item is included or not */
    protected $isIncluded;

    /**
     * Gets the item price.
     *
     * @since 0.1.0
     *
     * @return CurrencyAmount
     */
    public function getPrice() : CurrencyAmount
    {
        return $this->price;
    }

    /**
     * Sets the item price.
     *
     * @since 0.1.0
     *
     * @param CurrencyAmount $value
     * @return $this
     */
    public function setPrice(CurrencyAmount $value) : ShippingRateItem
    {
        $this->price = $value;

        return $this;
    }

    /**
     * Determines whether the item is included or not.
     *
     * @since 0.1.0
     *
     * @return bool
     */
    public function getIsIncluded() : bool
    {
        return $this->isIncluded;
    }

    /**
     * Sets the flag to determine whether the item is included or not.
     *
     * @since 0.1.0
     *
     * @param bool $value
     * @return $this
     */
    public function setIsIncluded(bool $value) : ShippingRateItem
    {
        $this->isIncluded = $value;

        return $this;
    }
}
