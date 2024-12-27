<?php

namespace GoDaddy\WordPress\MWC\Common\Models;

use DateTime;
use GoDaddy\WordPress\MWC\Common\Models\Orders\FeeItem;
use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Common\Models\Orders\ShippingItem;
use GoDaddy\WordPress\MWC\Common\Models\Orders\TaxItem;
use GoDaddy\WordPress\MWC\Common\Traits\CanBulkAssignPropertiesTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasNumericIdentifierTrait;

/**
 * Native cart object.
 */
class Cart extends AbstractModel
{
    use CanBulkAssignPropertiesTrait;
    use HasNumericIdentifierTrait;

    /** @var Coupon[] */
    protected $coupons = [];

    /** @var LineItem[] */
    protected $lineItems = [];

    /** @var CurrencyAmount|null */
    protected $lineAmount;

    /** @var ShippingItem[] */
    protected $shippingItems = [];

    /** @var CurrencyAmount|null */
    protected $shippingAmount;

    /** @var FeeItem[] */
    protected $feeItems = [];

    /** @var CurrencyAmount|null */
    protected $feeAmount;

    /** @var TaxItem[] */
    protected $taxItems = [];

    /** @var CurrencyAmount|null */
    protected $taxAmount;

    /** @var CurrencyAmount|null */
    protected $totalAmount;

    /** @var DateTime|null date created */
    protected $createdAt;

    /** @var DateTime|null date updated */
    protected $updatedAt;

    /**
     * Gets the cart coupons.
     *
     * @return Coupon[]
     */
    public function getCoupons()
    {
        return $this->coupons;
    }

    /**
     * Gets the cart line items.
     *
     * @return LineItem[]
     */
    public function getLineItems() : array
    {
        return $this->lineItems;
    }

    /**
     * Gets the line items amount.
     *
     * @return CurrencyAmount|null
     */
    public function getLineAmount()
    {
        return $this->lineAmount;
    }

    /**
     * Gets the cart shipping items.
     *
     * @return ShippingItem[]
     */
    public function getShippingItems() : array
    {
        return $this->shippingItems;
    }

    /**
     * Gets the shipping items amount.
     *
     * @return CurrencyAmount|null
     */
    public function getShippingAmount()
    {
        return $this->shippingAmount;
    }

    /**
     * Gets the cart fee items.
     *
     * @return FeeItem[]
     */
    public function getFeeItems() : array
    {
        return $this->feeItems;
    }

    /**
     * Gets the fee items amount.
     *
     * @return CurrencyAmount|null
     */
    public function getFeeAmount()
    {
        return $this->feeAmount;
    }

    /**
     * Gets the cart tax items.
     *
     * @return TaxItem[]
     */
    public function getTaxItems() : array
    {
        return $this->taxItems;
    }

    /**
     * Gets the tax items amount.
     *
     * @return CurrencyAmount|null
     */
    public function getTaxAmount()
    {
        return $this->taxAmount;
    }

    /**
     * Gets the cart total amount.
     *
     * @return CurrencyAmount|null
     */
    public function getTotalAmount()
    {
        return $this->totalAmount;
    }

    /**
     * Gets the date when the cart was created.
     *
     * @return DateTime|null
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Gets the date when the cart was last updated.
     *
     * @return DateTime|null
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Sets the cart coupons.
     *
     * @param Coupon[] $value
     * @return $this
     */
    public function setCoupons(array $value) : Cart
    {
        $this->coupons = $value;

        return $this;
    }

    /**
     * Sets the cart line items.
     *
     * @param LineItem[] $value
     * @return $this
     */
    public function setLineItems(array $value) : Cart
    {
        $this->lineItems = $value;

        return $this;
    }

    /**
     * Sets the line items amount.
     *
     * @param CurrencyAmount $value
     * @return $this
     */
    public function setLineAmount(CurrencyAmount $value) : Cart
    {
        $this->lineAmount = $value;

        return $this;
    }

    /**
     * Sets the cart shipping items.
     *
     * @param ShippingItem[] $value
     * @return $this
     */
    public function setShippingItems(array $value) : Cart
    {
        $this->shippingItems = $value;

        return $this;
    }

    /**
     * Sets the shipping items amount.
     *
     * @param CurrencyAmount $value
     * @return $this
     */
    public function setShippingAmount(CurrencyAmount $value) : Cart
    {
        $this->shippingAmount = $value;

        return $this;
    }

    /**
     * Sets the cart fee items.
     *
     * @param FeeItem[] $value
     * @return $this
     */
    public function setFeeItems(array $value) : Cart
    {
        $this->feeItems = $value;

        return $this;
    }

    /**
     * Sets the fee items amount.
     *
     * @param CurrencyAmount $value
     * @return $this
     */
    public function setFeeAmount(CurrencyAmount $value) : Cart
    {
        $this->feeAmount = $value;

        return $this;
    }

    /**
     * Sets the cart tax items.
     *
     * @param TaxItem[] $value
     * @return $this
     */
    public function setTaxItems(array $value) : Cart
    {
        $this->taxItems = $value;

        return $this;
    }

    /**
     * Sets the tax items amount.
     *
     * @param CurrencyAmount $value
     * @return $this
     */
    public function setTaxAmount(CurrencyAmount $value) : Cart
    {
        $this->taxAmount = $value;

        return $this;
    }

    /**
     * Sets the cart total amount.
     *
     * @param CurrencyAmount $value
     * @return $this
     */
    public function setTotalAmount(CurrencyAmount $value) : Cart
    {
        $this->totalAmount = $value;

        return $this;
    }

    /**
     * Sets the date when the cart was created.
     *
     * @param DateTime $value
     * @return $this
     */
    public function setCreatedAt(DateTime $value) : Cart
    {
        $this->createdAt = $value;

        return $this;
    }

    /**
     * Sets the date when the cart was last updated.
     *
     * @param DateTime $value
     * @return $this
     */
    public function setUpdatedAt(DateTime $value) : Cart
    {
        $this->updatedAt = $value;

        return $this;
    }
}
