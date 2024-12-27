<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\Adapters;

use GoDaddy\WordPress\MWC\Common\Helpers\SanitizationHelper;
use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;
use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Contracts\HasOrderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\Enums\LineItemMode;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\LineItem as LineItemDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\LineItemDetails;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\Adapters\Traits\CanGetProductFromLineItemTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\Contracts\DataObjectAdapterContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\SimpleMoney;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataSources\Adapters\SimpleMoneyAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\HasOrderTrait;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * Converts a Commerce line item data object into a native line item.
 */
class LineItemAdapter implements DataObjectAdapterContract, HasOrderContract
{
    use HasOrderTrait;
    use CanGetProductFromLineItemTrait;

    protected LineItemFulfillmentModeAdapter $lineItemFulfillmentModeAdapter;

    protected LineItemFulfillmentStatusAdapter $lineItemFulfillmentStatusAdapter;

    protected LineItemTypeAdapter $lineItemTypeAdapter;

    protected SimpleMoneyAdapter $simpleMoneyAdapter;

    protected LineItemDetailsAdapter $lineItemDetailsAdapter;

    protected LineItemProductRemoteIdAdapter $lineItemProductRemoteIdAdapter;

    protected LineItemTotalsAdapter $lineItemTotalsAdapter;

    /**
     * Constructor.
     *
     * @param LineItemFulfillmentModeAdapter $lineItemFulfillmentModeAdapter
     * @param LineItemFulfillmentStatusAdapter $lineItemFulfillmentStatusAdapter
     * @param LineItemTypeAdapter $lineItemTypeAdapter
     * @param SimpleMoneyAdapter $simpleMoneyAdapter
     * @param LineItemDetailsAdapter $lineItemDetailsAdapter
     * @param LineItemProductRemoteIdAdapter $lineItemProductRemoteIdAdapter
     * @param LineItemTotalsAdapter $lineItemTotalsAdapter
     */
    public function __construct(
        LineItemFulfillmentModeAdapter $lineItemFulfillmentModeAdapter,
        LineItemFulfillmentStatusAdapter $lineItemFulfillmentStatusAdapter,
        LineItemTypeAdapter $lineItemTypeAdapter,
        SimpleMoneyAdapter $simpleMoneyAdapter,
        LineItemDetailsAdapter $lineItemDetailsAdapter,
        LineItemProductRemoteIdAdapter $lineItemProductRemoteIdAdapter,
        LineItemTotalsAdapter $lineItemTotalsAdapter
    ) {
        $this->lineItemFulfillmentModeAdapter = $lineItemFulfillmentModeAdapter;
        $this->lineItemFulfillmentStatusAdapter = $lineItemFulfillmentStatusAdapter;
        $this->lineItemTypeAdapter = $lineItemTypeAdapter;
        $this->simpleMoneyAdapter = $simpleMoneyAdapter;
        $this->lineItemDetailsAdapter = $lineItemDetailsAdapter;
        $this->lineItemProductRemoteIdAdapter = $lineItemProductRemoteIdAdapter;
        $this->lineItemTotalsAdapter = $lineItemTotalsAdapter;
    }

    /**
     * Converts a Commerce line item data object into a native line item.
     *
     * @param LineItemDataObject $source
     */
    public function convertFromSource($source) : LineItem
    {
        $lineItem = LineItem::getNewInstance();

        $this->mapFulfillmentModeFromSource($source, $lineItem);
        $this->mapLineItemTotalsFromSource($source, $lineItem);

        return $this->mapLineItemDetailsFromSource($source, $lineItem)
            ->setName(SanitizationHelper::slug($source->name))
            ->setLabel($source->name)
            ->setQuantity($source->quantity)
            ->setFulfillmentStatus($this->lineItemFulfillmentStatusAdapter->convertFromSource($source->status))
            ->setSubTotalAmount($this->getSubTotalAmountFromSource($source));
    }

    /**
     * Maps line item details from source {@see LineItemDetails}.
     *
     * @param LineItemDataObject $source
     * @param LineItem $lineItem
     * @return LineItem
     */
    protected function mapLineItemDetailsFromSource(LineItemDataObject $source, LineItem $lineItem) : LineItem
    {
        return $this->lineItemDetailsAdapter->convertFromSource($source->details, $lineItem);
    }

    /**
     * Updates the totals in the given {@see LineItem} instance using the information from the source data object.
     */
    protected function mapLineItemTotalsFromSource(LineItemDataObject $source, LineItem $lineItem) : LineItem
    {
        return $this->lineItemTotalsAdapter->convertFromSource($source->totals, $lineItem);
    }

    /**
     * Maps fulfillment mode from source into the native {@see LineItem} model.
     *
     * @param LineItemDataObject $source
     * @param LineItem $lineItem
     * @return LineItem
     */
    protected function mapFulfillmentModeFromSource(LineItemDataObject $source, LineItem $lineItem) : LineItem
    {
        $properties = $this->lineItemFulfillmentModeAdapter->convertFromSource($source->fulfillmentMode);

        $lineItem->setIsVirtual($properties['isVirtual']);
        $lineItem->setIsDownloadable($properties['isDownloadable']);

        return $lineItem;
    }

    /**
     * Gets subtotal amount from source as native {@see CurrencyAmount} model.
     *
     * @param LineItemDataObject $source
     * @return CurrencyAmount
     */
    protected function getSubTotalAmountFromSource(LineItemDataObject $source) : CurrencyAmount
    {
        $subTotal = $this->simpleMoneyAdapter->convertFromSimpleMoney($source->unitAmount);

        $subTotal->setAmount((int) ($subTotal->getAmount() * $source->quantity));

        return $subTotal;
    }

    /**
     * Converts a native line item into a Commerce line item data object.
     *
     * @param LineItem $target
     *
     * @return LineItemDataObject
     */
    public function convertToSource($target) : LineItemDataObject
    {
        $product = $this->getProductFromLineItem($target);

        return new LineItemDataObject([
            'details'              => $this->getLineItemDetails($target, $product),
            'fulfillmentMode'      => $this->getLineItemFulfillmentMode($target),
            'fulfillmentChannelId' => $target->getFulfillmentChannelId(),
            'name'                 => $this->convertLineItemNameToSource($target),
            'productId'            => $this->lineItemProductRemoteIdAdapter->convertToSource($product),
            'quantity'             => $target->getQuantity(),
            'status'               => $this->lineItemFulfillmentStatusAdapter->convertToSource($target),
            'totals'               => $this->lineItemTotalsAdapter->convertToSource($target),
            'type'                 => $this->lineItemTypeAdapter->convertToSource($target),
            'unitAmount'           => $this->getLineItemUnitAmount($target),
        ]);
    }

    /**
     * Converts given line item name into a source product name.
     *
     * If the line item is a variation, we need to get the parent product's name.
     *
     * @param LineItem $lineItem
     * @return string
     */
    protected function convertLineItemNameToSource(LineItem $lineItem) : string
    {
        $wooProduct = $lineItem->getProduct();

        if ($wooProduct && 'variation' === $wooProduct->get_type()) {
            return $wooProduct->get_title();
        }

        return $lineItem->getLabel();
    }

    /**
     * Gets an instance {@see LineItemDetails} data object from the native {@see LineItem} model.
     *
     * @param LineItem $lineItem
     * @param Product|null $product
     * @return LineItemDetails
     */
    protected function getLineItemDetails(LineItem $lineItem, ?Product $product) : LineItemDetails
    {
        return $this->lineItemDetailsAdapter->setProduct($product)->convertToSource($lineItem);
    }

    /**
     * Gets the {@see LineItemMode} for the given line item.
     *
     * @return LineItemMode::*
     */
    protected function getLineItemFulfillmentMode(LineItem $lineItem) : string
    {
        return $this->lineItemFulfillmentModeAdapter->setOrder($this->getOrder())->convertToSource($lineItem);
    }

    /**
     * Gets an instance of {@see SimpleMoney} data object from the native {@see LineItem} model.
     *
     * @param LineItem $lineItem
     * @return SimpleMoney
     */
    protected function getLineItemUnitAmount(LineItem $lineItem) : SimpleMoney
    {
        $subTotal = $lineItem->getSubTotalAmount();

        return new SimpleMoney([
            'currencyCode' => $subTotal->getCurrencyCode(),
            'value'        => $lineItem->getQuantity() > 0 ? (int) ($subTotal->getAmount() / $lineItem->getQuantity()) : 0,
        ]);
    }
}
