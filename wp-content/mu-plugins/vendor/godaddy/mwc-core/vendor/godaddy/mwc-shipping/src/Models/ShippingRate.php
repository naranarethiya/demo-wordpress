<?php

namespace GoDaddy\WordPress\MWC\Shipping\Models;

use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;
use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;
use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasStringIdentifierTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasStringRemoteIdentifierTrait;
use GoDaddy\WordPress\MWC\Shipping\Contracts\ShippingServiceContract;
use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\CarrierContract;
use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\PackageTypeContract;
use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\ShippingRateContract;
use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\ShippingRateItemContract;

/**
 * Represents a shipping rate.
 *
 * @since 0.1.0
 */
class ShippingRate extends AbstractModel implements ShippingRateContract
{
    use HasLabelTrait;
    use HasStringIdentifierTrait;
    use HasStringRemoteIdentifierTrait;

    /** @var ShippingRateItemContract[] */
    protected $items = [];

    /** @var CurrencyAmount */
    protected $total;

    /** @var CarrierContract */
    protected $carrier;

    /** @var PackageTypeContract|null */
    protected ?PackageTypeContract $packageType = null;

    /** @var ShippingServiceContract */
    protected $service;

    /** @var bool */
    protected $isTrackable = false;

    /** @var int */
    protected $deliveryDays;

    /**
     * Gets the shipping rate items.
     *
     * @since 0.1.0
     *
     * @return array
     */
    public function getItems() : array
    {
        return $this->items;
    }

    /**
     * Sets the shipping rate items.
     *
     * @since 0.1.0
     *
     * @param ShippingRateItemContract ...$value
     * @return $this
     */
    public function setItems(ShippingRateItemContract ...$value) : ShippingRate
    {
        $this->items = $value;

        return $this;
    }

    /**
     * Adds an item to the shipping rate items.
     *
     * @since 0.1.0
     *
     * @param ShippingRateItemContract $item
     * @return $this
     */
    public function addItem(ShippingRateItemContract $item) : ShippingRate
    {
        $this->items[] = $item;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function addItems(ShippingRateItemContract ...$items)
    {
        $this->items = array_merge($this->items, $items);

        return $this;
    }

    /**
     * Gets the shipping rate total amount.
     *
     * @since 0.1.0
     *
     * @return CurrencyAmount
     */
    public function getTotal() : CurrencyAmount
    {
        return $this->total;
    }

    /**
     * Sets the shipping rate total amount.
     *
     * @since 0.1.0
     *
     * @param CurrencyAmount $value
     * @return $this
     */
    public function setTotal(CurrencyAmount $value) : ShippingRate
    {
        $this->total = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getCarrier() : CarrierContract
    {
        return $this->carrier;
    }

    /**
     * {@inheritDoc}
     */
    public function setCarrier(CarrierContract $value)
    {
        $this->carrier = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getPackageType() : ?PackageTypeContract
    {
        return $this->packageType;
    }

    /**
     * {@inheritDoc}
     */
    public function setPackageType(?PackageTypeContract $value)
    {
        $this->packageType = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getService() : ShippingServiceContract
    {
        return $this->service;
    }

    /**
     * {@inheritDoc}
     */
    public function setService(ShippingServiceContract $value)
    {
        $this->service = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getIsTrackable() : bool
    {
        return $this->isTrackable;
    }

    /**
     * {@inheritDoc}
     */
    public function setIsTrackable(bool $value)
    {
        $this->isTrackable = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getDeliveryDays() : int
    {
        return $this->deliveryDays;
    }

    /**
     * {@inheritDoc}
     */
    public function setDeliveryDays(int $value)
    {
        $this->deliveryDays = $value;

        return $this;
    }
}
