<?php

namespace GoDaddy\WordPress\MWC\Shipping\Contracts;

use GoDaddy\WordPress\MWC\Common\Models\Contracts\ModelContract;
use GoDaddy\WordPress\MWC\Common\Models\Dimensions;
use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Common\Models\Weight;
use GoDaddy\WordPress\MWC\Common\Traits\HasDimensionsTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasWeightTrait;
use GoDaddy\WordPress\MWC\Shipping\Models\ShippingLabel;
use GoDaddy\WordPress\MWC\Shipping\Models\ShippingRate;

/**
 * Package contract.
 *
 * Implementations using this contract could use the following traits:
 *
 * @see HasDimensionsTrait
 * @see HasWeightTrait
 */
interface PackageContract extends ModelContract, HasShipmentContract
{
    /**
     * Gets the package ID.
     *
     * @return string
     */
    public function getId() : string;

    /**
     * Sets the package ID.
     *
     * @param string $value
     * @return $this
     */
    public function setId(string $value) : PackageContract;

    /**
     * Gets the package items.
     *
     * @return LineItem[] associative array of items indexed by their IDs
     */
    public function getItems() : array;

    /**
     * Adds an item to the package.
     *
     * @param LineItem $item
     * @param float|int $quantity
     * @return $this
     */
    public function addItem(LineItem $item, float $quantity) : PackageContract;

    /**
     * Removes an item from the package.
     *
     * @param LineItem $item
     * @param float $quantityToRemove
     *
     * @return $this
     */
    public function removeItem(LineItem $item, float $quantityToRemove) : PackageContract;

    /**
     * Gets the quantity of an item in the package.
     *
     * @param LineItem $item item to get the quantity of in the package
     * @return float
     */
    public function getItemQuantity(LineItem $item) : float;

    /**
     * Determines whether a given item is present in the package.
     *
     * @param LineItem $item
     * @return bool
     */
    public function hasItem(LineItem $item) : bool;

    /**
     * Gets the package status.
     *
     * @return PackageStatusContract
     */
    public function getStatus() : PackageStatusContract;

    /**
     * Sets the package status.
     *
     * @param PackageStatusContract $status
     * @return $this
     */
    public function setStatus(PackageStatusContract $status) : PackageContract;

    /**
     * Determines whether the items in the package can be fulfilled.
     *
     * @see PackageStatusContract
     *
     * @return bool
     */
    public function canFulfillItems() : bool;

    /**
     * Gets the package dimensions.
     *
     * @see HasDimensionsTrait
     *
     * @return Dimensions
     */
    public function getDimensions() : Dimensions;

    /**
     * Sets the package dimensions.
     *
     * @see HasDimensionsTrait
     *
     * @param Dimensions $dimensions
     * @return $this
     */
    public function setDimensions(Dimensions $dimensions);

    /**
     * Gets the package weight.
     *
     * @see HasWeightTrait
     *
     * @return Weight|null
     */
    public function getWeight() : ?Weight;

    /**
     * Sets the package weight.
     *
     * @see HasWeightTrait
     *
     * @param Weight $weight
     * @return $this
     */
    public function setWeight(Weight $weight);

    /**
     * Gets the shipping rate associated with the package.
     *
     * @return ShippingRate|null
     */
    public function getShippingRate() : ?ShippingRate;

    /**
     * Sets a shipping rate to associate with the package.
     *
     * @param ShippingRate $shippingRate
     * @return $this
     */
    public function setShippingRate(ShippingRate $shippingRate) : PackageContract;

    /**
     * Gets the shipping label associated with the package.
     *
     * @return ShippingLabel|null
     */
    public function getShippingLabel() : ?ShippingLabel;

    /**
     * Sets a shipping label to associate with the package.
     *
     * @param ShippingLabel $shippingLabel
     * @return $this
     */
    public function setShippingLabel(ShippingLabel $shippingLabel) : PackageContract;

    /**
     * Gets the package tracking number.
     *
     * @return string|null
     */
    public function getTrackingNumber() : ?string;

    /**
     * Sets the package tracking number.
     *
     * @param string $value
     * @return $this
     */
    public function setTrackingNumber(string $value) : PackageContract;

    /**
     * Gets the package tracking URL.
     *
     * @return string|null
     */
    public function getTrackingUrl() : ?string;

    /**
     * Sets the package tracking URL.
     *
     * @param string $value
     * @return $this
     */
    public function setTrackingUrl(string $value) : PackageContract;
}
