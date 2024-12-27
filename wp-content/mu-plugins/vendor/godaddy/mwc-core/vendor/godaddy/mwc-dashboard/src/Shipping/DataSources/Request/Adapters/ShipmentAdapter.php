<?php

namespace GoDaddy\WordPress\MWC\Dashboard\Shipping\DataSources\Request\Adapters;

use DateTime;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\DataSources\Request\Adapters\AddressAdapter;
use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\Order\LineItemAdapter;
use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\WeightAdapter;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Models\Address;
use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Shipping\Contracts\PackageContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\ShipmentContract;
use GoDaddy\WordPress\MWC\Shipping\Models\Carrier;
use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\LabelDocumentContract;
use GoDaddy\WordPress\MWC\Shipping\Models\Packages\Package;
use GoDaddy\WordPress\MWC\Shipping\Models\Packages\Statuses\LabelCreatedPackageStatus;
use GoDaddy\WordPress\MWC\Shipping\Models\Shipment;
use GoDaddy\WordPress\MWC\Shipping\Models\ShippingLabel;
use GoDaddy\WordPress\MWC\Shipping\Models\ShippingRate;
use WC_Order_Item_Product;

/**
 * @method static static getNewInstance(array $data)
 */
class ShipmentAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var array source data */
    protected $data;

    /**
     * ShipmentAdapter constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Converts from Data Source format.
     *
     * @return ShipmentContract
     */
    public function convertFromSource() : ShipmentContract
    {
        return $this->convertShipmentFromSource()->addPackage($this->convertPackageFromSource());
    }

    /**
     * Converts the source array of data into an instance of {@see ShipmentContract}.
     *
     * @return ShipmentContract
     */
    protected function convertShipmentFromSource() : ShipmentContract
    {
        $shipment = (new Shipment())
            ->setId($this->getShipmentId())
            ->setCreatedAt(new DateTime())
            ->setUpdatedAt(new DateTime());

        $this->convertCarrierFromSource($shipment);
        $this->maybeConvertOriginAddressFromSource($shipment);
        $this->maybeConvertDestinationAddressFromSource($shipment);

        return $shipment;
    }

    /**
     * Converts the provider name and label into a {@see Carrier} object.
     *
     * @param ShipmentContract $shipment
     * @return void
     */
    protected function convertCarrierFromSource(ShipmentContract $shipment) : void
    {
        $providerName = (string) StringHelper::ensureScalar(ArrayHelper::get($this->data, 'shippingProvider'));

        $shipment->setProviderName($providerName);

        $carrier = (new Carrier())
            ->setId(StringHelper::generateUuid4())
            ->setName($providerName)
            ->setLabel('');

        if (strtolower($providerName) === 'other') {
            $providerLabel = ArrayHelper::get($this->data, 'otherShippingProviderDescription', 'Other');

            $shipment->setProviderLabel($providerLabel);
            $carrier->setLabel($providerLabel);
        }

        $shipment->setCarrier($carrier);
    }

    /**
     * Attempts to convert the origin address data into an {@see Address} object.
     *
     * @param ShipmentContract $shipment
     * @return void
     */
    protected function maybeConvertOriginAddressFromSource(ShipmentContract $shipment) : void
    {
        if ($originAddressData = ArrayHelper::get($this->data, 'originAddress')) {
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
        return AddressAdapter::getNewInstance($data)->convertFromSource();
    }

    /**
     * Attempts to convert the destination address data into an {@see Address} object.
     *
     * @param ShipmentContract $shipment
     * @return void
     */
    protected function maybeConvertDestinationAddressFromSource(ShipmentContract $shipment) : void
    {
        if ($destinationAddressData = ArrayHelper::get($this->data, 'destinationAddress')) {
            $shipment->setDestinationAddress($this->convertAddressFromSource($destinationAddressData));
        }
    }

    /**
     * Converts the source array of data into an instance of {@see PackageContract}.
     *
     * @return PackageContract
     */
    protected function convertPackageFromSource() : PackageContract
    {
        $package = (new Package())
            ->setId(StringHelper::generateUuid4())
            ->setStatus(new LabelCreatedPackageStatus());

        if ($trackingNumber = ArrayHelper::get($this->data, 'trackingNumber')) {
            $package->setTrackingNumber($trackingNumber);
        }

        if ($trackingUrl = ArrayHelper::get($this->data, 'trackingUrl')) {
            $package->setTrackingUrl($trackingUrl);
        }

        if ($weightData = ArrayHelper::get($this->data, 'weight')) {
            $package->setWeight(
                WeightAdapter::getNewInstance(
                    ArrayHelper::get($weightData, 'value'),
                    ArrayHelper::get($weightData, 'unit')
                )->convertFromSource()
            );
        }

        foreach (ArrayHelper::get($this->data, 'items', []) as $item) {
            $lineItem = $this->createLineItem($item);
            $package->addItem($lineItem, ArrayHelper::get($item, 'quantity', 0));
        }

        return $package;
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

        // Assume that each Shipment only has one package.
        $package = array_values($shipment->getPackages())[0];
        $items = $package->getItems();

        $shippingRate = $package->getShippingRate();
        $shippingLabel = $package->getShippingLabel();

        $providerName = $shippingRate ? $shippingRate->getCarrier()->getName() : $shipment->getProviderName();
        $providerDescription = '';
        if ($shippingRate && 'other' === strtolower($shipment->getProviderName())) {
            $providerDescription = $shippingRate->getCarrier()->getLabel();
        } elseif ('other' === strtolower($shipment->getProviderName())) {
            $providerDescription = $shipment->getProviderLabel();
        }

        $data = [
            'id'                               => $shipment->getId(),
            'createdAt'                        => $shipment->getCreatedAt() ? $shipment->getCreatedAt()->format('c') : '',
            'updatedAt'                        => $shipment->getUpdatedAt() ? $shipment->getUpdatedAt()->format('c') : '',
            'shippingProvider'                 => $providerName,
            'otherShippingProviderDescription' => $providerDescription,
            'trackingNumber'                   => $package->getTrackingNumber(),
            'trackingUrl'                      => $shipment->getPackageTrackingUrl($package),
            'status'                           => $package->getStatus()->getName(),
            'rate'                             => $this->createShippingRateData($shippingRate),
            'label'                            => $shippingLabel ? $this->createShippingLabelData($shippingLabel) : null,
            'items'                            => [],
        ];

        foreach ($items as $item) {
            $data['items'][] = [
                'id'       => $item->getId(),
                'quantity' => $package->getItemQuantity($item),
            ];
        }

        return $data;
    }

    /**
     * Takes ShippingRate object and converts it to an array formatted for the response.
     *
     * @param ?ShippingRate $shippingRate
     * @return array<string, mixed>|null
     */
    protected function createShippingRateData(?ShippingRate $shippingRate) : ?array
    {
        return ShippingRateAdapter::getNewInstance([])->convertToSource($shippingRate);
    }

    /**
     * Takes ShippingLabel object and converts it to an array formatted for the response.
     *
     * @param ShippingLabel $shippingLabel
     * @return array<string, array<int|string, mixed>|bool|string|null>
     */
    protected function createShippingLabelData(ShippingLabel $shippingLabel) : array
    {
        $documents = $shippingLabel->getDocuments();

        return [
            'status'      => $shippingLabel->getStatus()->getName(),
            'documents'   => ! empty($documents) ? $this->getShippingLabelDocuments($documents) : null,
            'isTrackable' => $shippingLabel->getIsTrackable(),
        ];
    }

    /**
     * Takes LabelDocumentContract object and converts it to an array of documents formatted for the response.
     *
     * @param labelDocumentContract[] $documents
     * @return array<int|string, mixed>
     */
    protected function getShippingLabelDocuments(array $documents) : array
    {
        $labelDocuments = [];
        foreach ($documents as $document) {
            $docArray = $document->toArray();
            $labelDocuments[ArrayHelper::get($docArray, 'format', '')] = ArrayHelper::get($docArray, 'url', '');
        }

        return $labelDocuments;
    }

    /**
     * Takes an array of data about a line item and converts it to a LineItem object using the LineItemAdapter.
     *
     * @param array $itemData
     * @return LineItem
     */
    protected function createLineItem(array $itemData) : LineItem
    {
        $orderItem = new WC_Order_Item_Product(ArrayHelper::get($itemData, 'id', 0));

        return LineItemAdapter::getNewInstance($orderItem)->convertFromSource();
    }

    /**
     * Gets the Shipment ID from the source data, or generates a UUID.
     *
     * @return string
     */
    private function getShipmentId() : string
    {
        return $this->data['id'] ?? StringHelper::generateUuid4();
    }
}
