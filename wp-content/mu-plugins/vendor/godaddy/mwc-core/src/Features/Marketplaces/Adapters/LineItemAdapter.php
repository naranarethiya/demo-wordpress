<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Adapters;

use GoDaddy\WordPress\MWC\Common\Contracts\FulfillmentStatusContract;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\SanitizationHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\ProductsRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\Subscribers\OrderWebhookSubscriber;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\LineItem;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Traits\ConvertsMarketplacesAmountTrait;
use GoDaddy\WordPress\MWC\Core\Payments\Exceptions\InvalidProductException;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\OrderAdapter;
use GoDaddy\WordPress\MWC\Shipping\Models\Orders\FulfillmentStatuses\FulfilledFulfillmentStatus;
use GoDaddy\WordPress\MWC\Shipping\Models\Orders\FulfillmentStatuses\PartiallyFulfilledFulfillmentStatus;
use GoDaddy\WordPress\MWC\Shipping\Models\Orders\FulfillmentStatuses\UnfulfilledFulfillmentStatus;
use WC_Product;

/**
 * Adapts data from a GDM webhook to a native core order object.
 *
 * @method static static getNewInstance(array $lineItem)
 */
class LineItemAdapter implements DataSourceAdapterContract
{
    use ConvertsMarketplacesAmountTrait;
    use CanGetNewInstanceTrait;

    /** @var array<string, mixed> Line item data from the webhook */
    protected $source;

    /**
     * LineItemAdapter constructor.
     *
     * @param array<string, mixed> $lineItem Line item data from the webhook payload.
     */
    public function __construct(array $lineItem)
    {
        $this->source = $lineItem;
    }

    /**
     * Gets a new line item object.
     *
     * Sets an ID equal to 0 to let WooCommerce save (insert) the line item later for an adapted order.
     * @see OrderAdapter::convertFromSource()
     * @see OrderWebhookSubscriber::handlePayload()
     *
     * @return LineItem
     */
    private function getNewLineItem() : LineItem
    {
        /* @phpstan-ignore-next-line */
        return LineItem::getNewInstance()->setId(0);
    }

    /**
     * Converts a source line item from a GDM payload to a core native order.
     *
     * @return LineItem
     * @throws InvalidProductException
     */
    public function convertFromSource() : LineItem
    {
        $quantity = TypeHelper::float(ArrayHelper::get($this->source, 'quantity', 0.0), 0.00);
        $unitPrice = TypeHelper::float(ArrayHelper::get($this->source, 'unit_price', 0.00), 0.00);
        $subtotal = $this->adaptCurrencyAmount($unitPrice * $quantity);

        /* @phpstan-ignore-next-line */
        return $this->getNewLineItem()
            ->setOrderItemReference(ArrayHelper::get($this->source, 'order_item_ref'))
            ->setQuantity($quantity)
            ->setLabel(ArrayHelper::get($this->source, 'title') ?: '')
            ->setName(SanitizationHelper::slug(ArrayHelper::get($this->source, 'title') ?: ''))
            ->setSubTotalAmount($subtotal)
            // We do not use the "total" property here because Woo's total does not include tax, but the Sellbrite "total" does.
            // In order to be compatible with Woo we need to use the amount without tax.
            ->setTotalAmount($subtotal)
            ->setSubTotalTaxAmount($this->parseAndConvertAmountFromSource('tax'))
            ->setTaxAmount($this->parseAndConvertAmountFromSource('tax'))
            ->setProduct($this->convertProductFromSource())
            ->setFulfillmentStatus($this->convertFulfillmentStatusFromSource());
    }

    /**
     * {@inheritDoc}
     */
    public function convertToSource() : array
    {
        // Not implemented.
        return [];
    }

    /**
     * Converts a source line item product data from a GDM payload to a core native order.
     *
     * @return WC_Product
     *
     * @throws InvalidProductException
     */
    protected function convertProductFromSource() : WC_Product
    {
        $productId = (int) ArrayHelper::get($this->source, 'gdwoo_product_id', 0);

        $product = ProductsRepository::get($productId);

        if (! $product) {
            throw new InvalidProductException('Product not found');
        }

        return $product;
    }

    /**
     * Converts a source line item fulfillment status data from a GDM payload to a core native order.
     *
     * @return FulfillmentStatusContract
     */
    protected function convertFulfillmentStatusFromSource() : FulfillmentStatusContract
    {
        $quantity = (float) ArrayHelper::get($this->source, 'quantity', 0.0);
        $quantityFulfilled = (float) ArrayHelper::get($this->source, 'quantity_fulfilled', 0.0);

        if ($quantityFulfilled === $quantity) {
            return new FulfilledFulfillmentStatus();
        }

        if ($quantityFulfilled > 0) {
            return new PartiallyFulfilledFulfillmentStatus();
        }

        return new UnfulfilledFulfillmentStatus();
    }
}
