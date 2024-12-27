<?php

namespace GoDaddy\WordPress\MWC\Dashboard\Shipping\DataSources\Request\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\CurrencyAmountAdapter;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Shipping\Contracts\ShippingServiceContract;
use GoDaddy\WordPress\MWC\Shipping\Models\Carrier;
use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\CarrierContract;
use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\PackageType;
use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\PackageTypeContract;
use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\ShippingRateContract;
use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\ShippingRateItemContract;
use GoDaddy\WordPress\MWC\Shipping\Models\ShippingRate;
use GoDaddy\WordPress\MWC\Shipping\Models\ShippingService;

/**
 * @method static static getNewInstance(array $source)
 */
class ShippingRateAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var array<string, mixed> */
    protected $source;

    /**
     * @param array<string, mixed> $source
     */
    public function __construct(array $source)
    {
        $this->source = $source;
    }

    /**
     * Converts an array of data into a {@see ShippingRateContract} instance.
     *
     * @return ShippingRateContract
     */
    public function convertFromSource() : ShippingRateContract
    {
        $shippingRate = (new ShippingRate())
            ->setId($this->convertIdFromSource())
            ->setCarrier($this->convertCarrierFromSource())
            ->setPackageType($this->convertPackageTypeFromSource())
            ->setService($this->convertShippingServiceFromSource())
            ->setItems(...$this->convertShippingRateItemsFromSource())
            ->setTotal($this->convertTotalFromSource())
            ->setIsTrackable((bool) $this->getValueFromSource('isTrackable', false))
            ->setDeliveryDays((int) $this->getValueFromSource('deliveryDays', 0));

        if ($remoteId = $this->convertRemoteIdFromSource()) {
            $shippingRate->setRemoteId($remoteId);
        }

        return $shippingRate;
    }

    /**
     * Converts the package type from the source, if possible.
     *
     * @return PackageTypeContract|null
     */
    protected function convertPackageTypeFromSource() : ?PackageTypeContract
    {
        if (! $code = $this->getStringValueFromSource('packageType.code')) {
            return null;
        }

        return PackageType::seed([
            'code'        => $code,
            'name'        => $this->getStringValueFromSource('packageType.name'),
            'description' => $this->getStringValueFromSource('packageType.description'),
        ]);
    }

    /**
     * Converts the package type to the source array, if possible.
     *
     * @param PackageTypeContract|null $packageType
     *
     * @return array<string, mixed>|null
     */
    protected function convertPackageTypeToSource(?PackageTypeContract $packageType) : ?array
    {
        return $packageType ? $packageType->toArray() : null;
    }

    /**
     * Converts the source data into the ID of the shipping rate.
     *
     * @return string
     */
    protected function convertIdFromSource() : string
    {
        return $this->getStringValueFromSource('rateId');
    }

    /**
     * Converts the source data into the remote ID of the shipping rate.
     *
     * @return string
     */
    protected function convertRemoteIdFromSource() : string
    {
        return $this->convertIdFromSource();
    }

    /**
     * Converts carrier data into a {@see CarrierContract} instance.
     *
     * @return CarrierContract
     */
    protected function convertCarrierFromSource() : CarrierContract
    {
        return (new Carrier())
            ->setId($this->getStringValueFromSource('carrier.id'))
            ->setName($this->getStringValueFromSource('carrier.name'))
            ->setLabel($this->getStringValueFromSource('carrier.label'));
    }

    /**
     * Converts shipping service data into an instance of {@see ShippingServiceContract} instance.
     *
     * @return ShippingServiceContract
     */
    protected function convertShippingServiceFromSource() : ShippingServiceContract
    {
        return (new ShippingService())
            ->setName($this->getStringValueFromSource('service.name'))
            ->setLabel($this->getStringValueFromSource('service.label'));
    }

    /**
     * Converts shipping rate items data into an array of {@see ShippingRateItemContract} instances.
     *
     * @return ShippingRateItemContract[]
     */
    protected function convertShippingRateItemsFromSource() : array
    {
        return [];
    }

    /**
     * Converts an array of data for the total cost into a {@see CurrencyAmount} instance.
     *
     * @return CurrencyAmount
     */
    protected function convertTotalFromSource() : CurrencyAmount
    {
        return $this->convertCurrencyAmountFromSource(
            ArrayHelper::wrap(ArrayHelper::get($this->source, 'total'))
        );
    }

    /**
     * Gets a bool value from the given array of data.
     *
     * @param array<string, mixed> $data
     * @param string $key
     * @return bool
     */
    protected function getBoolValue(array $data, string $key) : bool
    {
        return (bool) StringHelper::ensureScalar(ArrayHelper::get($data, $key));
    }

    /**
     * Converts a {@see ShippingRateContract} object into an array of data.
     *
     * @param ShippingRateContract|null $shippingRate
     * @return array<string, mixed>|null
     */
    public function convertToSource(?ShippingRateContract $shippingRate = null) : ?array
    {
        return $shippingRate ? $this->convertShippingRateToSource($shippingRate) : null;
    }

    /**
     * Converts a {@see ShippingRateContract} object into an array of data.
     *
     * @param ShippingRateContract $shippingRate
     * @return array<string, mixed>
     */
    protected function convertShippingRateToSource(ShippingRateContract $shippingRate) : array
    {
        return [
            'rateId'       => $shippingRate->getId(),
            'carrier'      => $this->convertCarrierToSource($shippingRate->getCarrier()),
            'packageType'  => $this->convertPackageTypeToSource($shippingRate->getPackageType()),
            'service'      => $this->convertServiceToSource($shippingRate->getService()),
            'total'        => $this->convertCurrencyAmountToSource($shippingRate->getTotal()),
            'items'        => $this->convertShippingRateItemsToSource($shippingRate->getItems()),
            'isTrackable'  => $shippingRate->getIsTrackable(),
            'deliveryDays' => $shippingRate->getDeliveryDays(),
        ];
    }

    /**
     * Converts a {@see CarrierContract} object into an array of data.
     *
     * @param CarrierContract $carrier
     * @return array<string, mixed>
     */
    protected function convertCarrierToSource(CarrierContract $carrier) : array
    {
        return [
            'id'    => $carrier->getId(),
            'name'  => $carrier->getName(),
            'label' => $carrier->getLabel(),
        ];
    }

    /**
     * Converts a {@see ShippingServiceContract} object into an array of data.
     *
     * @param ShippingServiceContract $service
     * @return array<string, mixed>
     */
    protected function convertServiceToSource(ShippingServiceContract $service) : array
    {
        return [
            'name'  => $service->getName(),
            'label' => $service->getLabel(),
        ];
    }

    /**
     * Converts an array of {@see ShippingRateItemContract} objects into an array of data.
     *
     * @param ShippingRateItemContract[] $items
     * @return array<string, mixed>
     */
    protected function convertShippingRateItemsToSource(array $items) : array
    {
        return [];
    }

    /**
     * Converts a {@see CurrencyAmount} object into an array of data.
     *
     * @param CurrencyAmount $amount
     * @return array<string, mixed>
     */
    protected function convertCurrencyAmountToSource(CurrencyAmount $amount) : array
    {
        $adapter = (new CurrencyAmountAdapter(0, ''));

        return [
            'currency' => $amount->getCurrencyCode(),
            'amount'   => $adapter->convertToSource($amount),
        ];
    }

    /**
     * Converts an array of data into {@see CurrencyAmount} object.
     *
     * @param array<string, mixed> $data
     * @return CurrencyAmount
     */
    protected function convertCurrencyAmountFromSource(array $data) : CurrencyAmount
    {
        return (new CurrencyAmountAdapter(
            ArrayHelper::get($data, 'amount', 0),
            ArrayHelper::get($data, 'currency', '')
        ))->convertFromSource();
    }

    /**
     * Gets a value from an array by key.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getValueFromSource(string $key, $default = null)
    {
        return ArrayHelper::get($this->source, $key, $default) ?? $default;
    }

    /**
     * Gets a string value from the source array.
     *
     * Returns an empty string if the value cannot be converted to string.
     *
     * @param string $key
     * @return string
     */
    protected function getStringValueFromSource(string $key) : string
    {
        return $this->getStringValue($this->source, $key);
    }

    /**
     * Gets a string value from the given array of data.
     *
     * @param array<string, mixed> $data
     * @param string $key
     * @return string
     */
    protected function getStringValue(array $data, string $key) : string
    {
        return (string) StringHelper::ensureScalar(ArrayHelper::get($data, $key));
    }
}
