<?php

namespace GoDaddy\WordPress\MWC\Common\Models\Orders;

use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;
use GoDaddy\WordPress\MWC\Common\Traits\FulfillableTrait;
use WC_Product;

/**
 * A representation of a line Item in an order.
 */
class LineItem extends AbstractOrderItem
{
    use FulfillableTrait;

    /** @var int|float the line item's quantity */
    protected $quantity;

    /** @var WC_Product|null line item product */
    protected $product;

    /** @var int|null line item product variation ID */
    protected $variationId;

    /** @var string|null the SKU of the line item product (if set) */
    protected $sku;

    /** @var bool|null whether the line item product (when set) is virtual */
    protected $isVirtual;

    /** @var bool|null whether the line item product (when set) is downloadable */
    protected $isDownloadable;

    /** @var CurrencyAmount the line item's total tax amount */
    protected $taxAmount;

    /** @var CurrencyAmount the line item's subtotal amount (before discounts) */
    protected $subTotalAmount;

    /** @var CurrencyAmount the line item's subtotal tax amount (before discounts) */
    protected $subTotalTaxAmount;

    /**
     * Gets the line item amount.
     *
     * @return int|float
     * @phpstan-ignore-next-line
     */
    public function getQuantity() : float
    {
        return $this->quantity;
    }

    /**
     * Gets the line item tax total amount.
     *
     * @return CurrencyAmount
     */
    public function getTaxAmount() : CurrencyAmount
    {
        return $this->taxAmount;
    }

    /**
     * Gets the line item's subtotal amount (before discounts).
     *
     * @return CurrencyAmount
     */
    public function getSubTotalAmount() : CurrencyAmount
    {
        return $this->subTotalAmount;
    }

    /**
     * Gets the line item's subtotal tax amount (before discounts).
     *
     * @return CurrencyAmount
     */
    public function getSubTotalTaxAmount() : CurrencyAmount
    {
        return $this->subTotalTaxAmount;
    }

    /**
     * Gets the line item product.
     *
     * @return WC_Product|null
     */
    public function getProduct() : ?WC_Product
    {
        return $this->product instanceof WC_Product ? $this->product : null;
    }

    /**
     * Gets the line item product SKU.
     *
     * @return string|null
     */
    public function getSku() : ?string
    {
        return $this->sku;
    }

    /**
     * Gets the value whether the line item product is virtual.
     *
     * @return bool|null
     */
    public function getIsVirtual() : ?bool
    {
        return $this->isVirtual;
    }

    /**
     * Gets the value whether the line item product is downloadable.
     *
     * @return bool|null
     */
    public function getIsDownloadable() : ?bool
    {
        return $this->isDownloadable;
    }

    /**
     * Gets the line item variationId for variable products.
     *
     * @return int|null
     */
    public function getVariationId() : ?int
    {
        return $this->variationId;
    }

    /**
     * Sets the line item quantity.
     *
     * @param int|float $value
     * @return $this
     * @phpstan-ignore-next-line
     */
    public function setQuantity(float $value) : LineItem
    {
        $this->quantity = $value;

        return $this;
    }

    /**
     * Sets the line item tax total amount.
     *
     * @param CurrencyAmount $value
     * @return $this
     */
    public function setTaxAmount(CurrencyAmount $value) : LineItem
    {
        $this->taxAmount = $value;

        return $this;
    }

    /**
     * Sets the line item's subtotal amount (before discounts).
     *
     * @param CurrencyAmount $value
     * @return $this
     */
    public function setSubTotalAmount(CurrencyAmount $value) : LineItem
    {
        $this->subTotalAmount = $value;

        return $this;
    }

    /**
     * Sets the line item's subtotal tax amount (before discounts).
     *
     * @param CurrencyAmount $value
     * @return $this
     */
    public function setSubTotalTaxAmount(CurrencyAmount $value) : LineItem
    {
        $this->subTotalTaxAmount = $value;

        return $this;
    }

    /**
     * Sets the line item product.
     *
     * @param WC_Product|bool|null $value
     * @return $this
     */
    public function setProduct($value) : LineItem
    {
        $this->product = $value instanceof WC_Product ? $value : null;

        return $this;
    }

    /**
     * Sets the line item variationId for variable products.
     *
     * @param int|null $value
     * @return $this
     */
    public function setVariationId(?int $value = null) : LineItem
    {
        $this->variationId = $value;

        return $this;
    }

    /**
     * Sets the line item product SKU.
     *
     * @param string|null $value
     * @return $this
     */
    public function setSku(?string $value) : LineItem
    {
        $this->sku = $value;

        return $this;
    }

    /**
     * Sets whether the line item product is virtual.
     *
     * @param bool|null $value
     * @return $this
     */
    public function setIsVirtual(?bool $value) : LineItem
    {
        $this->isVirtual = $value;

        return $this;
    }

    /**
     * Sets whether the line item product is downloadable.
     *
     * @param bool|null $value
     * @return $this
     */
    public function setIsDownloadable(?bool $value) : LineItem
    {
        $this->isDownloadable = $value;

        return $this;
    }
}
