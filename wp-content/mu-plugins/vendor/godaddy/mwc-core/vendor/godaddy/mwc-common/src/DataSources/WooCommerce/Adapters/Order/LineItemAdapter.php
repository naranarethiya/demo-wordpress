<?php

namespace GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\Order;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\SanitizationHelper;
use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use WC_Order;
use WC_Order_Item_Product;
use WC_Product;

/**
 * Order line item adapter.
 *
 * Converts between a native order line item object and a WooCommerce order product item object.
 *
 * @property WC_Order_Item_Product $source
 * @method static static getNewInstance(WC_Order_Item_Product $source)
 * @method static static for(WC_Order_Item_Product $source, ?WC_Order $sourceOrder = null)
 */
class LineItemAdapter extends AbstractOrderItemAdapter implements DataSourceAdapterContract
{
    /**
     * Order line item adapter constructor.
     *
     * @param WC_Order_Item_Product $source
     */
    public function __construct(WC_Order_Item_Product $source)
    {
        $this->source = $source;
    }

    /**
     * Converts a WooCommerce order product item to a native order line item.
     *
     * @return LineItem
     */
    public function convertFromSource() : LineItem
    {
        $lineItem = LineItem::getNewInstance()
            ->setId($this->source->get_id())
            ->setLabel($this->source->get_name())
            ->setName(SanitizationHelper::slug($this->source->get_name()))
            ->setQuantity($this->source->get_quantity())
            ->setProduct($this->source->get_product())
            ->setVariationId($this->source->get_variation_id())
            ->setTaxAmount($this->convertCurrencyAmountFromSource((float) $this->source->get_total_tax()))
            ->setTotalAmount($this->convertCurrencyAmountFromSource((float) $this->source->get_total()))
            ->setSubTotalAmount($this->convertCurrencyAmountFromSource((float) $this->source->get_subtotal()))
            ->setSubTotalTaxAmount($this->convertCurrencyAmountFromSource((float) $this->source->get_subtotal_tax()));

        $this->convertLineItemProductPropertiesFromSource($lineItem);

        return $lineItem;
    }

    /**
     * Converts properties of the source WooCommerce product from the line item to native line item properties.
     *
     * @param LineItem $lineItem
     * @return void
     */
    protected function convertLineItemProductPropertiesFromSource(LineItem $lineItem) : void
    {
        $wcProduct = $this->source->get_product();

        if (! $wcProduct instanceof WC_Product) {
            return;
        }

        $lineItem->setSku((string) $wcProduct->get_sku());
        $lineItem->setNeedsShipping((bool) $wcProduct->needs_shipping());
        $lineItem->setIsVirtual((bool) $wcProduct->is_virtual());
        $lineItem->setIsDownloadable((bool) $wcProduct->is_downloadable());
    }

    /**
     * Converts a native order line item into a WooCommerce order product item.
     *
     * @param LineItem|null $lineItem
     * @return WC_Order_Item_Product
     */
    public function convertToSource(?LineItem $lineItem = null) : WC_Order_Item_Product
    {
        if (! $lineItem instanceof LineItem) {
            return $this->source;
        }

        $this->source->set_id($lineItem->getId());
        $this->source->set_name($lineItem->getLabel());
        $this->source->set_quantity($lineItem->getQuantity()); /* @phpstan-ignore-line */

        $product = $lineItem->getProduct();

        if ($product instanceof WC_Product) {
            $this->source->set_product($product);
        }

        if ($variationId = $lineItem->getVariationId()) {
            $this->source->set_variation_id($variationId);
        }

        $this->source->set_total_tax((string) $this->convertCurrencyAmountToSource($lineItem->getTaxAmount()));
        $this->source->set_total((string) $this->convertCurrencyAmountToSource($lineItem->getTotalAmount()));
        $this->source->set_subtotal((string) $this->convertCurrencyAmountToSource($lineItem->getSubTotalAmount()));
        $this->source->set_subtotal_tax((string) $this->convertCurrencyAmountToSource($lineItem->getSubTotalTaxAmount()));

        return $this->source;
    }
}
