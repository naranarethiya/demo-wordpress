<?php

namespace GoDaddy\WordPress\MWC\Dashboard\Shipping\DataSources\WooCommerce\Adapters;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;
use GoDaddy\WordPress\MWC\Dashboard\Shipping\DataSources\Request\Adapters\ShippingRateAdapter as RequestShippingRateAdapter;
use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\ShippingRateContract;
use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\ShippingRateItemContract;
use GoDaddy\WordPress\MWC\Shipping\Models\ShippingRateItem;

class ShippingRateAdapter extends RequestShippingRateAdapter
{
    /** {@inheritDoc} */
    protected function convertIdFromSource() : string
    {
        return $this->getStringValueFromSource('id');
    }

    /** {@inheritDoc} */
    protected function convertRemoteIdFromSource() : string
    {
        return $this->getStringValueFromSource('remoteId');
    }

    /** {@inheritDoc} */
    protected function convertShippingRateItemsFromSource() : array
    {
        return array_map(function (array $data) {
            return $this->convertShippingRateItemFromSource($data);
        }, ArrayHelper::wrap(ArrayHelper::get($this->source, 'items')));
    }

    /**
     * Converts an array of data into an instance of {@see ShippingRateItemContract}.
     *
     * @param array<string, mixed> $data
     * @return ShippingRateItemContract
     */
    protected function convertShippingRateItemFromSource(array $data) : ShippingRateItemContract
    {
        return (new ShippingRateItem())
            ->setName($this->getStringValue($data, 'name'))
            ->setLabel($this->getStringValue($data, 'label'))
            ->setPrice($this->convertCurrencyAmountFromSource(
                ArrayHelper::wrap(ArrayHelper::get($data, 'price')))
            )
            ->setIsIncluded($this->getBoolValue($data, 'isIncluded'));
    }

    /** {@inheritDoc} */
    protected function convertCurrencyAmountFromSource(array $data) : CurrencyAmount
    {
        return (new CurrencyAmount())
            ->setAmount((int) ArrayHelper::get($data, 'amount', 0))
            ->setCurrencyCode(ArrayHelper::get($data, 'currency', ''));
    }

    /**
     * Converts a {@see ShippingRateContract} object into an array of data.
     *
     * @param ShippingRateContract $shippingRate
     * @return array<string, mixed>
     */
    protected function convertShippingRateToSource(ShippingRateContract $shippingRate) : array
    {
        return ArrayHelper::whereNotNull([
            'id'           => $shippingRate->getId(),
            'remoteId'     => $shippingRate->getRemoteId(),
            'carrier'      => $this->convertCarrierToSource($shippingRate->getCarrier()),
            'service'      => $this->convertServiceToSource($shippingRate->getService()),
            'total'        => $this->convertCurrencyAmountToSource($shippingRate->getTotal()),
            'items'        => $this->convertShippingRateItemsToSource($shippingRate->getItems()),
            'isTrackable'  => $shippingRate->getIsTrackable(),
            'deliveryDays' => $shippingRate->getDeliveryDays(),
        ]);
    }

    /** {@inheritDoc} */
    protected function convertShippingRateItemsToSource(array $items) : array
    {
        return array_map(static function (ShippingRateItemContract $item) {
            return $item->toArray();
        }, $items);
    }
}
