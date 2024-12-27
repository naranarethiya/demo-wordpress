<?php

namespace GoDaddy\WordPress\MWC\Dashboard\Shipping\DataSources\WooCommerce\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\Order\LineItemAdapter;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Shipping\Contracts\LabelStatusContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\PackageContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\PackageStatusContract;
use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\ShippingLabelContract;
use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\ShippingRateContract;
use GoDaddy\WordPress\MWC\Shipping\Models\Labels\Statuses\CompletedLabelStatus;
use GoDaddy\WordPress\MWC\Shipping\Models\Labels\Statuses\ErrorLabelStatus;
use GoDaddy\WordPress\MWC\Shipping\Models\Labels\Statuses\ProcessingLabelStatus;
use GoDaddy\WordPress\MWC\Shipping\Models\Labels\Statuses\VoidedLabelStatus;
use GoDaddy\WordPress\MWC\Shipping\Models\Packages\Package;
use GoDaddy\WordPress\MWC\Shipping\Models\Packages\Statuses\CancelledPackageStatus;
use GoDaddy\WordPress\MWC\Shipping\Models\Packages\Statuses\CreatedPackageStatus;
use GoDaddy\WordPress\MWC\Shipping\Models\Packages\Statuses\LabelCreatedPackageStatus;
use GoDaddy\WordPress\MWC\Shipping\Models\Packages\Statuses\NotTrackedPackageStatus;
use GoDaddy\WordPress\MWC\Shipping\Models\RemoteLabelDocument;
use GoDaddy\WordPress\MWC\Shipping\Models\ShippingLabel;
use GoDaddy\WordPress\MWC\Shipping\Models\ShippingRate;
use WC_Order_Item_Product;

/**
 * Used to convert shipment data stored as order meta into instances of PackageContract.
 */
class PackageAdapter implements DataSourceAdapterContract
{
    /** @var array source data */
    protected $source = [];

    /**
     * Sets the value for the source property.
     *
     * @param array $source
     */
    public function __construct(array $source)
    {
        $this->source = $source;
    }

    /**
     * Creates an instance of PackageContract using the source data.
     *
     * @return PackageContract
     */
    public function convertFromSource() : PackageContract
    {
        $package = (new Package())
            ->setId(ArrayHelper::get($this->source, 'id', StringHelper::generateUuid4()))
            ->setStatus($this->getStatusFromSource(ArrayHelper::get($this->source, 'status', '')));

        if ($trackingNumber = ArrayHelper::get($this->source, 'trackingNumber')) {
            $package->setTrackingNumber($trackingNumber);
        }

        if ($trackingUrl = ArrayHelper::get($this->source, 'trackingUrl')) {
            $package->setTrackingUrl($trackingUrl);
        }

        foreach (ArrayHelper::get($this->source, 'items', []) as $item) {
            if ($lineItem = $this->getLineItemFromSource((int) ArrayHelper::get($item, 'id', 0))) {
                $package->addItem($lineItem, ArrayHelper::get($item, 'quantity', 0));
            }
        }

        $this->mayConvertShippingLabelFromSource($package);
        $this->mayConvertShippingRateFromSource($package);

        return $package;
    }

    /**
     * May converts shipping label from source if exists and attach to given package.
     *
     * @param Package $package
     * @return Package
     */
    protected function mayConvertShippingLabelFromSource(Package $package) : Package
    {
        $label = $this->convertShippingLabelFromSource();

        if ($label instanceof ShippingLabel) {
            $package->setShippingLabel($label);
        }

        return $package;
    }

    /**
     * May converts shipping rate from source if exists and attach to given package.
     *
     * @param Package $package
     * @return Package
     */
    protected function mayConvertShippingRateFromSource(Package $package) : Package
    {
        $rate = $this->convertShippingRateFromSource();

        if ($rate instanceof ShippingRate) {
            $package->setShippingRate($rate);
        }

        return $package;
    }

    /**
     * Converts shipping rate from source data into {@see ShippingRate} object.
     *
     * @return ShippingRateContract|null
     */
    protected function convertShippingRateFromSource() : ?ShippingRateContract
    {
        if (! $data = ArrayHelper::get($this->source, 'rate')) {
            return null;
        }

        return ShippingRateAdapter::getNewInstance(ArrayHelper::wrap($data))->convertFromSource();
    }

    /**
     * Converts shipping label from source data into {@see ShippingLabel} object.
     *
     * @return ShippingLabel|null
     */
    protected function convertShippingLabelFromSource() : ?ShippingLabel
    {
        // TODO: we need to create a proper shipping label and label document adapter MWC-7471 {nmolham 2022-08-05}
        if (! $data = ArrayHelper::get($this->source, 'label')) {
            return null;
        }

        $label = (new ShippingLabel())
            ->setId(ArrayHelper::get($data, 'id', ''))
            ->setStatus($this->getShippingLabelStatusFromSource(
                (string) StringHelper::ensureScalar(ArrayHelper::get($data, 'status'))
            ))
            ->setIsTrackable(ArrayHelper::get($data, 'isTrackable', false));

        if ($remoteId = ArrayHelper::get($data, 'remoteId')) {
            $label->setRemoteId((string) $remoteId);
        }

        if ($documentsData = ArrayHelper::get($data, 'documents')) {
            $documentsData = ArrayHelper::wrap($documentsData);

            $documents = [];
            foreach ($documentsData as $documentData) {
                $documents[] = (new RemoteLabelDocument())
                    ->setFormat(ArrayHelper::get($documentData, 'format', '') ?? '')
                    ->setUrl(ArrayHelper::get($documentData, 'url', '') ?? '');
            }

            $label->setDocuments(...$documents);
        }

        return $label;
    }

    /**
     * Gets a label status instance that matches the given status name.
     *
     * TODO: remove these methods when we have an adapter for {@see LabelStatusContract} -- https://jira.godaddy.com/browse/MWC-7847 {wvega 2022-08-26}
     *
     * @param string $statusName status name
     *
     * @return LabelStatusContract
     */
    protected function getShippingLabelStatusFromSource(string $statusName) : LabelStatusContract
    {
        foreach ($this->getLabelStatusOptions() as $statusClassName) {
            $status = new $statusClassName();

            if ($status->getName() === $statusName) {
                return $status;
            }
        }

        return new CompletedLabelStatus();
    }

    /**
     * Gets an array of possible label status instances.
     *
     * @return class-string<LabelStatusContract>[]
     */
    protected function getLabelStatusOptions() : array
    {
        return [
            CompletedLabelStatus::class,
            ProcessingLabelStatus::class,
            ErrorLabelStatus::class,
            VoidedLabelStatus::class,
        ];
    }

    /**
     * Gets a package status instance that matches the given status name.
     *
     * @param string $statusName status name
     *
     * @return PackageStatusContract
     */
    protected function getStatusFromSource(string $statusName) : PackageStatusContract
    {
        foreach ($this->getStatusOptions() as $statusClassName) {
            $status = new $statusClassName();

            if ($status->getName() === $statusName) {
                return $status;
            }
        }

        return new LabelCreatedPackageStatus();
    }

    /**
     * Gets an array of class names of possible package status instances.
     *
     * @return class-string<PackageStatusContract>[]
     */
    protected function getStatusOptions() : array
    {
        return [
            CreatedPackageStatus::class,
            NotTrackedPackageStatus::class,
            LabelCreatedPackageStatus::class,
            CancelledPackageStatus::class,
        ];
    }

    /**
     * Returns an array representation of the package object properties.
     *
     * @param PackageContract|null $package
     * @return array<string, mixed>
     */
    public function convertToSource(PackageContract $package = null) : array
    {
        if (null === $package) {
            return [];
        }

        $items = [];
        foreach ($package->getItems() as $item) {
            $array['id'] = $item->getId();
            $array['quantity'] = $package->getItemQuantity($item);
            $items[] = $array;
        }

        $label = $package->getShippingLabel();
        $rate = $package->getShippingRate();

        return ArrayHelper::whereNotNull([
            'id'             => $package->getId(),
            'status'         => $package->getStatus()->getName(),
            'trackingNumber' => $package->getTrackingNumber() ?: '',
            'trackingUrl'    => $package->getTrackingUrl() ?: '',
            'items'          => $items,
            'label'          => $label ? $this->convertShippingLabelToSource($label) : null,
            'rate'           => $rate ? $rate->toArray() : null,
        ]);
    }

    /**
     * Gets an array representation of the shipping label object properties.
     *
     * @param ShippingLabelContract $shippingLabel
     * @return array<string, mixed>
     */
    protected function convertShippingLabelToSource(ShippingLabelContract $shippingLabel) : array
    {
        $data = $shippingLabel->toArray();

        $data['status'] = $shippingLabel->getStatus()->getName();

        return $data;
    }

    /**
     * Gets a {@see LineItem} instance for the given ID.
     *
     * @param int $itemId WooCommerce item ID
     *
     * @return LineItem|null
     */
    protected function getLineItemFromSource(int $itemId) : ?LineItem
    {
        try {
            $wcOrderItem = $this->getWooCommerceOrderItem($itemId);
        } catch (Exception $e) {
            return null;
        }

        return $this->getLineItemAdapter($wcOrderItem)->convertFromSource();
    }

    /**
     * Gets the new WC_Order_Item_Product instance for the given id.
     *
     * @param int|null $id
     * @return WC_Order_Item_Product
     */
    protected function getWooCommerceOrderItem(int $id = null) : WC_Order_Item_Product
    {
        return new WC_Order_Item_Product($id);
    }

    /**
     * Gets a new instance of {@see LineItemAdapter}.
     *
     * @param WC_Order_Item_Product $wcOrderItem WooCommerce order item object
     *
     * @return LineItemAdapter
     */
    protected function getLineItemAdapter(WC_Order_Item_Product $wcOrderItem) : LineItemAdapter
    {
        return new LineItemAdapter($wcOrderItem);
    }
}
