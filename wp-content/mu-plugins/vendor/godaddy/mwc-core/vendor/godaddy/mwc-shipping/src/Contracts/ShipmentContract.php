<?php

namespace GoDaddy\WordPress\MWC\Shipping\Contracts;

use DateTime;
use GoDaddy\WordPress\MWC\Common\Contracts\HasStringRemoteIdentifierContract;
use GoDaddy\WordPress\MWC\Common\Models\Address;
use GoDaddy\WordPress\MWC\Common\Models\Contracts\ModelContract;
use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\CarrierContract;
use GoDaddy\WordPress\MWC\Shipping\Models\ShippingRate;

/**
 * Shipment contract.
 */
interface ShipmentContract extends ModelContract, HasStringRemoteIdentifierContract
{
    /**
     * Gets the shipment ID.
     *
     * @return string
     */
    public function getId() : string;

    /**
     * Sets the shipment ID.
     *
     * @param string $value
     * @return ShipmentContract
     */
    public function setId(string $value) : ShipmentContract;

    /**
     * Gets the shipment origin address.
     *
     * @return Address|null
     */
    public function getOriginAddress() : ?Address;

    /**
     * Sets the shipment origin address.
     *
     * @param Address|null $value
     * @return $this
     */
    public function setOriginAddress(?Address $value) : ShipmentContract;

    /**
     * Gets the shipment destination address.
     *
     * @return Address|null
     */
    public function getDestinationAddress() : ?Address;

    /**
     * Sets the shipment destination address.
     *
     * @param Address|null $value
     * @return $this
     */
    public function setDestinationAddress(?Address $value) : ShipmentContract;

    /**
     * Gets the shipment provider's name.
     *
     * @return string
     */
    public function getProviderName() : string;

    /**
     * Sets the shipment provider's name.
     *
     * @param string $value
     * @return $this
     */
    public function setProviderName(string $value) : ShipmentContract;

    /**
     * Gets the label for the shipping provider associated with this shipment object.
     *
     * @return string|null
     */
    public function getProviderLabel() : ?string;

    /**
     * Sets the shipment provider's label.
     *
     * @param string $value
     * @return $this
     */
    public function setProviderLabel(string $value) : ShipmentContract;

    /**
     * Gets the shipping service.
     *
     * @return ShippingServiceContract
     */
    public function getService() : ShippingServiceContract;

    /**
     * Sets the shipping service.
     *
     * @param ShippingServiceContract $value
     * @return $this
     */
    public function setService(ShippingServiceContract $value);

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
     * Gets the shipping rate.
     *
     * @return ShippingRate
     */
    public function getShippingRate() : ShippingRate;

    /**
     * Sets the shipping rate.
     *
     * @param ShippingRate $value
     * @return $this
     */
    public function setShippingRate(ShippingRate $value) : ShipmentContract;

    /**
     * Gets the packages in the shipment.
     *
     * @return PackageContract[] array of packages
     */
    public function getPackages() : array;

    /**
     * Sets the packages in the shipment.
     *
     * @param PackageContract[] $packages
     *
     * @return $this
     */
    public function setPackages(array $packages) : ShipmentContract;

    /**
     * Adds a package to the shipment.
     *
     * @param PackageContract $package
     * @return $this
     */
    public function addPackage(PackageContract $package) : ShipmentContract;

    /**
     * Adds multiple packages to the shipment.
     *
     * @param PackageContract[] $packages
     * @return $this
     */
    public function addPackages(array $packages) : ShipmentContract;

    /**
     * Removes a package from the shipment.
     *
     * @param PackageContract $package
     * @return $this
     */
    public function removePackage(PackageContract $package) : ShipmentContract;

    /**
     * Removes multiple packages from the shipment.
     *
     * @param PackageContract[] $packages
     * @return ShipmentContract
     */
    public function removePackages(array $packages) : ShipmentContract;

    /**
     * Determines whether a package is in the shipment.
     *
     * @param PackageContract $package
     * @return bool
     */
    public function hasPackage(PackageContract $package) : bool;

    /**
     * Gets an array of packages where canFulfillItems() returns true.
     *
     * @return array
     */
    public function getPackagesThatCanFulfillItems() : array;

    /**
     * Gets the tracking URL for the given package.
     *
     * @param PackageContract $package
     * @return string|null
     */
    public function getPackageTrackingUrl(PackageContract $package) : ?string;

    /**
     * Sets the value of the properties included in the given array.
     *
     * NOTE: this method doesn't define a return type because it causes a conflict with the implementation from the CanBulkAssignPropertiesTrait trait {wvega 2021-06-22}
     *
     * @param array $data
     * @return $this
     */
    public function setProperties(array $data);

    /**
     * Converts all class properties to an array.
     *
     * @return array
     */
    public function toArray() : array;

    /**
     * Sets the created at date for the shipment.
     *
     * @param DateTime $value
     * @return ShipmentContract
     */
    public function setCreatedAt(DateTime $value) : ShipmentContract;

    /**
     * Gets the created at date for the shipment.
     *
     * @return DateTime|null
     */
    public function getCreatedAt() : ?DateTime;

    /**
     * Sets the updated at date for the shipment.
     *
     * @param DateTime $value
     * @return ShipmentContract
     */
    public function setUpdatedAt(DateTime $value) : ShipmentContract;

    /**
     * Gets the updated at date for the shipment.
     *
     * @return DateTime|null
     */
    public function getUpdatedAt() : ?DateTime;
}
