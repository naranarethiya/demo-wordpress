<?php

namespace GoDaddy\WordPress\MWC\Dashboard\Shipping\DataSources\WooCommerce\Adapters;

use DateTime;
use DateTimeInterface;
use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\DataSources\Request\Adapters\AddressAdapter;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Models\Address;
use GoDaddy\WordPress\MWC\Shipping\Contracts\PackageContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\ShipmentContract;
use GoDaddy\WordPress\MWC\Shipping\Models\Carrier;
use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\CarrierContract;
use GoDaddy\WordPress\MWC\Shipping\Models\Shipment;

class ShipmentAdapter implements DataSourceAdapterContract
{
    /** @var array source data */
    protected $source;

    /**
     * ShipmentAdapter constructor.
     *
     * @since x.y.z
     *
     * @param array $source
     */
    public function __construct(array $source)
    {
        $this->source = $source;
    }

    /**
     * Converts from Data Source format.
     *
     * @since x.y.z
     *
     * @return ShipmentContract
     */
    public function convertFromSource() : ShipmentContract
    {
        $shipment = (new Shipment())
            ->setId(ArrayHelper::get($this->source, 'id', ''))
            ->setProviderName((string) ArrayHelper::get($this->source, 'providerName'))
            ->setProviderLabel((string) ArrayHelper::get($this->source, 'providerLabel'));

        $this->convertCarrierFromSource($shipment);
        $this->maybeConvertOriginAddressFromSource($shipment);
        $this->maybeConvertDestinationAddressFromSource($shipment);

        if ($createdAt = $this->timestampToDateTime(ArrayHelper::get($this->source, 'createdAt', ''))) {
            $shipment->setCreatedAt($createdAt);
        }

        if ($updateAt = $this->timestampToDateTime(ArrayHelper::get($this->source, 'updatedAt', ''))) {
            $shipment->setUpdatedAt($updateAt);
        }

        $convertedPackages = array_map(static function ($packageSource) {
            return (new PackageAdapter($packageSource))->convertFromSource();
        }, ArrayHelper::get($this->source, 'packages', []));

        $shipment->addPackages($convertedPackages);

        return $shipment;
    }

    /**
     * Converts to Data Source format.
     *
     * @param ShipmentContract|null $shipment
     * @return array
     */
    public function convertToSource(ShipmentContract $shipment = null) : array
    {
        if (! $shipment) {
            return [];
        }

        $carrier = $shipment->getCarrier();
        $originAddress = $shipment->getOriginAddress();
        $destinationAddress = $shipment->getDestinationAddress();

        $convertedShipment = [
            'id'                 => $shipment->getId(),
            'providerName'       => $shipment->getProviderName(),
            'providerLabel'      => $shipment->getProviderLabel() ?: '',
            'createdAt'          => $shipment->getCreatedAt() ? $shipment->getCreatedAt()->format(DateTimeInterface::ATOM) : '',
            'updatedAt'          => $shipment->getUpdatedAt() ? $shipment->getUpdatedAt()->format(DateTimeInterface::ATOM) : '',
            'carrier'            => $carrier->toArray(),
            'originAddress'      => $this->convertAddressToSource($originAddress),
            'destinationAddress' => $this->convertAddressToSource($destinationAddress),
        ];

        foreach ($shipment->getPackages() as $package) {
            $convertedShipment['packages'][] = $this->convertPackageToSource($shipment, $package);
        }

        return $convertedShipment;
    }

    /**
     * Converts package data to Data Source format.
     *
     * @since x.y.z
     *
     * @param ShipmentContract $shipment
     * @param PackageContract $package
     * @return array
     */
    protected function convertPackageToSource(ShipmentContract $shipment, PackageContract $package) : array
    {
        $packageSource = (new PackageAdapter([]))->convertToSource($package);
        $packageSource['generatedTrackingUrl'] = $shipment->getPackageTrackingUrl($package);

        return $packageSource;
    }

    /**
     * Converts string timestamp into DateTime object.
     *
     * @param string $timestamp
     * @return DateTime|null
     */
    protected function timestampToDateTime(string $timestamp) : ?DateTime
    {
        try {
            return new DateTime($timestamp);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Converts the provider name and label into a {@see Carrier} object.
     *
     * @param ShipmentContract $shipment
     * @return void
     */
    protected function convertCarrierFromSource(ShipmentContract $shipment) : void
    {
        $shipment
            ->setProviderName((string) StringHelper::ensureScalar(ArrayHelper::get($this->source, 'providerName')))
            ->setProviderLabel((string) StringHelper::ensureScalar(ArrayHelper::get($this->source, 'providerLabel')));

        if ($data = ArrayHelper::wrap(ArrayHelper::get($this->source, 'carrier'))) {
            $carrier = $this->convertCarrierFromSourceUsingCarrierData($data);
        } else {
            $carrier = $this->convertCarrierFromSourceUsingProviderData($shipment);
        }

        if (strtolower($shipment->getProviderName()) === 'other') {
            $providerLabel = $shipment->getProviderLabel() ?: 'Other';

            $shipment->setProviderLabel($providerLabel);
            $carrier->setLabel($providerLabel);
        }

        $shipment->setCarrier($carrier);
    }

    /**
     * Converts source data into a {@see Carrier} object.
     *
     * @param array<string, mixed> $data
     * @return CarrierContract
     */
    protected function convertCarrierFromSourceUsingCarrierData(array $data) : CarrierContract
    {
        return (new Carrier())
            ->setId((string) StringHelper::ensureScalar(ArrayHelper::get($data, 'id')) ?: StringHelper::generateUuid4())
            ->setName((string) StringHelper::ensureScalar(ArrayHelper::get($data, 'name')))
            ->setLabel((string) StringHelper::ensureScalar(ArrayHelper::get($data, 'label')));
    }

    /**
     * Converts shipment provider data into a {@see Carrier} object.
     *
     * This method will convert shipments that were stored before we started using the carrier property.
     *
     * @param ShipmentContract $shipment
     * @return CarrierContract
     */
    protected function convertCarrierFromSourceUsingProviderData(ShipmentContract $shipment) : CarrierContract
    {
        return (new Carrier())
            ->setId(StringHelper::generateUuid4())
            ->setName($shipment->getProviderName());
    }

    /**
     * Attempts to convert the origin address data into an {@see Address} object.
     *
     * @param ShipmentContract $shipment
     * @return void
     */
    protected function maybeConvertOriginAddressFromSource(ShipmentContract $shipment) : void
    {
        if ($originAddressData = ArrayHelper::get($this->source, 'originAddress')) {
            $shipment->setOriginAddress($this->convertAddressFromSource($originAddressData));
        }
    }

    /**
     * Converts the given address data into an {@see Address} object.
     *
     * @param array<string, mixed> $data
     * @return Address
     */
    protected function convertAddressFromSource(array $data) : Address
    {
        return AddressAdapter::getNewInstance($this->convertPhoneNumber($data))->convertFromSource();
    }

    /**
     * Converts the given {@see Address} object into an address array.
     *
     * @param Address|null $address
     * @return array<string, array<string>|string|null>
     */
    protected function convertAddressToSource(?Address $address) : array
    {
        return AddressAdapter::getNewInstance([])->convertToSource($address) ?: [];
    }

    /**
     * Converts the phone property in given address data to phoneNumber.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed> $data
     */
    protected function convertPhoneNumber(array $data) : array
    {
        // bail if we already have phoneNumber
        if (ArrayHelper::get($data, 'phoneNumber')) {
            return $data;
        }

        // if not, convert from phone
        $phoneNumber = ArrayHelper::get($data, 'phone', '');

        if (is_string($phoneNumber)) {
            $phoneNumber = preg_replace('/[^+0-9]/', '', $phoneNumber);
        }

        ArrayHelper::set($data, 'phoneNumber', $phoneNumber);
        ArrayHelper::remove($data, 'phone');

        return $data;
    }

    /**
     * Attempts to convert the destination address data into an {@see Address} object.
     *
     * @param ShipmentContract $shipment
     * @return void
     */
    protected function maybeConvertDestinationAddressFromSource(ShipmentContract $shipment) : void
    {
        if ($destinationAddressData = ArrayHelper::get($this->source, 'destinationAddress')) {
            $shipment->setDestinationAddress($this->convertAddressFromSource($destinationAddressData));
        }
    }
}
