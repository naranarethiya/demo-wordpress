<?php

namespace GoDaddy\WordPress\MWC\Shipping\Models;

use BadMethodCallException;
use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;
use GoDaddy\WordPress\MWC\Common\Models\Address;
use GoDaddy\WordPress\MWC\Common\Providers\Contracts\ProviderContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanBulkAssignPropertiesTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasStringRemoteIdentifierTrait;
use GoDaddy\WordPress\MWC\Shipping\Contracts\PackageContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\ShipmentContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\ShippingServiceContract;
use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\CarrierContract;
use GoDaddy\WordPress\MWC\Shipping\Shipping;

/**
 * Object representation of a shipment.
 */
class Shipment extends AbstractModel implements ShipmentContract
{
    use CanBulkAssignPropertiesTrait;
    use HasStringRemoteIdentifierTrait;

    /** @var string shipment identifier */
    protected $id;

    /** @var Address|null shipment origin address */
    protected $originAddress;

    /** @var Address|null shipment destination address */
    protected $destinationAddress;

    /** @var string shipment provider's name */
    protected $providerName;

    /** @var string shipment provider's label */
    protected $providerLabel;

    /** @var ShippingServiceContract shipping service for the shipment */
    protected $service;

    /** @var CarrierContract that carrier responsible for this shipment */
    protected $carrier;

    /** @var PackageContract[] array of packages indexed by their IDs */
    protected $packages = [];

    /** @var ShippingRate associated shipping rate */
    protected $shippingRate;

    /** @var DateTime timestamp record was created */
    protected $createdAt;

    /** @var DateTime timestamp record was updated */
    protected $updatedAt;

    /**
     * Gets the shipment ID.
     *
     * @return string
     */
    public function getId() : string
    {
        return $this->id;
    }

    /**
     * Sets the shipment ID.
     *
     * @param string $value
     * @return $this
     */
    public function setId(string $value) : ShipmentContract
    {
        $this->id = $value;

        return $this;
    }

    /**
     * Gets the shipment origin address.
     *
     * @return Address|null
     */
    public function getOriginAddress() : ?Address
    {
        return $this->originAddress ?? null;
    }

    /**
     * Sets the shipment origin address.
     *
     * @param Address|null $value
     * @return $this
     */
    public function setOriginAddress(?Address $value) : ShipmentContract
    {
        $this->originAddress = $value;

        return $this;
    }

    /**
     * Gets the shipment destination address.
     *
     * @return Address|null
     */
    public function getDestinationAddress() : ?Address
    {
        return $this->destinationAddress;
    }

    /**
     * Sets the shipment destination address.
     *
     * @param Address|null $value
     * @return $this
     */
    public function setDestinationAddress(?Address $value) : ShipmentContract
    {
        $this->destinationAddress = $value;

        return $this;
    }

    /**
     * Gets the shipment provider's name.
     *
     * @return string
     */
    public function getProviderName() : string
    {
        return $this->providerName;
    }

    /**
     * Sets the shipment provider's name.
     *
     * @param string $value
     * @return $this
     */
    public function setProviderName(string $value) : ShipmentContract
    {
        $this->providerName = $value;

        return $this;
    }

    /**
     * Gets the label for the shipping provider associated with this shipment object.
     *
     * @return string|null
     */
    public function getProviderLabel() : ?string
    {
        return $this->providerLabel;
    }

    /**
     * Sets the shipment provider's label.
     *
     * @param string $value
     * @return $this
     */
    public function setProviderLabel(string $value) : ShipmentContract
    {
        $this->providerLabel = $value;

        return $this;
    }

    /**
     * Gets the shipping service.
     *
     * @return ShippingServiceContract
     */
    public function getService() : ShippingServiceContract
    {
        return $this->service;
    }

    /**
     * Sets the shipping service.
     *
     * @param ShippingServiceContract $value
     * @return $this
     */
    public function setService(ShippingServiceContract $value)
    {
        $this->service = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCarrier() : CarrierContract
    {
        return $this->carrier;
    }

    /**
     * {@inheritdoc}
     */
    public function setCarrier(CarrierContract $value) : Shipment
    {
        $this->carrier = $value;

        return $this;
    }

    /**
     * Gets the shipping rate.
     *
     * @return ShippingRate
     */
    public function getShippingRate() : ShippingRate
    {
        return $this->shippingRate;
    }

    /**
     * Sets the shipping rate.
     *
     * @param ShippingRate $value
     * @return $this
     */
    public function setShippingRate(ShippingRate $value) : ShipmentContract
    {
        $this->shippingRate = $value;

        return $this;
    }

    /**
     * Gets the packages in the shipment.
     *
     * @return PackageContract[] array of packages indexed by their IDs
     */
    public function getPackages() : array
    {
        return $this->packages;
    }

    /**
     * Adds a package in the shipment.
     *
     * @param PackageContract $package
     * @return $this
     */
    public function addPackage(PackageContract $package) : ShipmentContract
    {
        $package->setShipment($this);
        $this->packages[$package->getId()] = $package;

        return $this;
    }

    /**
     * Adds multiple packages to the shipment.
     *
     * @param PackageContract[] $packages
     * @return $this
     */
    public function addPackages(array $packages) : ShipmentContract
    {
        foreach ($packages as $package) {
            $this->addPackage($package);
        }

        return $this;
    }

    /**
     * Sets the packages in the shipment.
     *
     * This method replaces the list of packages currently in the shipment with the given list of packages.
     *
     * @param PackageContract[] $packages
     *
     * @return $this
     */
    public function setPackages(array $packages) : ShipmentContract
    {
        $this->packages = [];

        $this->addPackages($packages);

        return $this;
    }

    /**
     * Removes a package from the shipment.
     *
     * @param PackageContract $package
     * @return $this
     */
    public function removePackage(PackageContract $package) : ShipmentContract
    {
        unset($this->packages[$package->getId()]);

        return $this;
    }

    /**
     * Removes multiple packages from the shipment.
     *
     * @param PackageContract[] $packages
     * @return $this
     */
    public function removePackages(array $packages) : ShipmentContract
    {
        foreach ($packages as $package) {
            $this->removePackage($package);
        }

        return $this;
    }

    /**
     * Determines whether a package is in the shipment.
     *
     * @param PackageContract $package
     * @return bool
     */
    public function hasPackage(PackageContract $package) : bool
    {
        return isset($this->packages[$package->getId()]);
    }

    /**
     * Gets an array of packages where canFulfillItems() returns true.
     *
     * @return array
     */
    public function getPackagesThatCanFulfillItems() : array
    {
        return ArrayHelper::where($this->getPackages(), static function (PackageContract $package) {
            return $package->canFulfillItems();
        });
    }

    /**
     * Gets the tracking URL for the given package.
     *
     * @param PackageContract $package
     * @return string|null
     */
    public function getPackageTrackingUrl(PackageContract $package) : ?string
    {
        if (! $this->hasPackage($package)) {
            return null;
        }

        return $package->getTrackingUrl() ?: $this->getPackageTrackingUrlUsingProvider($package);
    }

    /**
     * Gets the tracking URL for the given package using the instance of the shipping provider associated with this shipment.
     *
     * @param PackageContract $package
     * @return string|null
     */
    protected function getPackageTrackingUrlUsingProvider(PackageContract $package) : ?string
    {
        $provider = $this->getProvider();

        if (! $provider) {
            return null;
        }

        try {
            $tracking = $provider->tracking();
        } catch (BadMethodCallException $exception) {
            return null;
        }

        if (! is_callable([$tracking, 'getTrackingUrl'])) {
            return null;
        }

        return $tracking->getTrackingUrl($package->getTrackingNumber());
    }

    /**
     * Sets created at.
     *
     * @param DateTime $value
     * @return ShipmentContract
     */
    public function setCreatedAt(DateTime $value) : ShipmentContract
    {
        $this->createdAt = $value;

        return $this;
    }

    /**
     * Gets created at.
     *
     * @return DateTime|null
     */
    public function getCreatedAt() : ?DateTime
    {
        return $this->createdAt;
    }

    /**
     * Sets updated at.
     *
     * @param DateTime $value
     * @return ShipmentContract
     */
    public function setUpdatedAt(DateTime $value) : ShipmentContract
    {
        $this->updatedAt = $value;

        return $this;
    }

    /**
     * Gets updated at.
     *
     * @return DateTime|null
     */
    public function getUpdatedAt() : ?DateTime
    {
        return $this->updatedAt;
    }

    /**
     * Gets an instance of the shipping provider associated with this shipment object.
     *
     * @return ProviderContract|null
     */
    protected function getProvider() : ?ProviderContract
    {
        $shipping = Shipping::getInstance();

        try {
            return $shipping->provider($this->getProviderName());
        } catch (Exception $exception) {
            return null;
        }
    }
}
