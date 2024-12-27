<?php

namespace GoDaddy\WordPress\MWC\Shipping\Models\Contracts;

use GoDaddy\WordPress\MWC\Common\Contracts\HasStringIdentifierContract;
use GoDaddy\WordPress\MWC\Common\Contracts\HasStringRemoteIdentifierContract;
use GoDaddy\WordPress\MWC\Common\Models\Contracts\ModelContract;
use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;
use GoDaddy\WordPress\MWC\Shipping\Contracts\ShippingServiceContract;

interface ShippingRateContract extends ModelContract, HasStringRemoteIdentifierContract, HasStringIdentifierContract
{
    /**
     * Gets the carrier.
     *
     * @return CarrierContract
     */
    public function getCarrier() : CarrierContract;

    /**
     * Sets the carrier.
     *
     * @param CarrierContract $value
     * @return $this
     */
    public function setCarrier(CarrierContract $value);

    /**
     * Gets the package type.
     *
     * @return ?PackageTypeContract
     */
    public function getPackageType() : ?PackageTypeContract;

    /**
     * Sets the package type.
     *
     * @param ?PackageTypeContract $value
     * @return $this
     */
    public function setPackageType(?PackageTypeContract $value);

    /**
     * Gets the service.
     *
     * @return ShippingServiceContract
     */
    public function getService() : ShippingServiceContract;

    /**
     * Sets the service.
     *
     * @param ShippingServiceContract $value
     * @return $this
     */
    public function setService(ShippingServiceContract $value);

    /**
     * Gets the items.
     *
     * @return ShippingRateItemContract[]
     */
    public function getItems() : array;

    /**
     * Sets the items.
     *
     * @param ShippingRateItemContract ...$value
     * @return $this
     */
    public function setItems(ShippingRateItemContract ...$value);

    /**
     * Adds the items.
     *
     * @param ShippingRateItemContract ...$items
     * @return $this
     */
    public function addItems(ShippingRateItemContract ...$items);

    /**
     * Gets if this is trackable.
     *
     * @return bool
     */
    public function getIsTrackable() : bool;

    /**
     * Sets if this is trackable.
     *
     * @param bool $value
     * @return $this
     */
    public function setIsTrackable(bool $value);

    /**
     * Gets the number of delivery days.
     *
     * @return int
     */
    public function getDeliveryDays() : int;

    /**
     * Sets the number of delivery days.
     *
     * @param int $value
     * @return $this
     */
    public function setDeliveryDays(int $value);

    /**
     * Gets the total.
     *
     * @return CurrencyAmount
     */
    public function getTotal() : CurrencyAmount;

    /**
     * Sets the total.
     *
     * @param CurrencyAmount $value
     * @return $this
     */
    public function setTotal(CurrencyAmount $value);
}
