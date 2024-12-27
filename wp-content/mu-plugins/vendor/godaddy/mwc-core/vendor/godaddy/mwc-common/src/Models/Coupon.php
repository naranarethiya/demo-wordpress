<?php

namespace GoDaddy\WordPress\MWC\Common\Models;

use DateTime;
use GoDaddy\WordPress\MWC\Common\Traits\CanBulkAssignPropertiesTrait;
use GoDaddy\WordPress\MWC\Common\Traits\CanConvertToArrayTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasNumericIdentifierTrait;

/**
 * Native coupon object.
 */
class Coupon extends AbstractModel
{
    use CanBulkAssignPropertiesTrait, CanConvertToArrayTrait {
        CanConvertToArrayTrait::toArray as traitToArray;
    }
    use HasNumericIdentifierTrait;

    /** @var string|null code */
    protected $code;

    /** @var string|null discount type */
    protected $discountType;

    /** @var float|null discount amount */
    protected $discountAmount;

    /** @var bool|null whether the coupon grants free shipping or not */
    protected $allowsFreeShipping;

    /** @var DateTime|null expiration date */
    protected $expiryDate;

    /**
     * Gets the coupon code.
     *
     * @return string|null
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Gets the coupon discount type.
     *
     * @return string|null
     */
    public function getDiscountType()
    {
        return $this->discountType;
    }

    /**
     * Gets the coupon discount amount.
     *
     * @return float|null
     */
    public function getDiscountAmount()
    {
        return $this->discountAmount;
    }

    /**
     * Returns whether the coupon grants free shipping or not.
     *
     * @return bool|null
     */
    public function getAllowsFreeShipping()
    {
        return $this->allowsFreeShipping;
    }

    /**
     * Gets the coupon expiration date.
     *
     * @return DateTime|null
     */
    public function getExpiryDate()
    {
        return $this->expiryDate;
    }

    /**
     * Sets the coupon code.
     *
     * @param string $value
     * @return $this
     */
    public function setCode(string $value) : Coupon
    {
        $this->code = $value;

        return $this;
    }

    /**
     * Sets the coupon discount type.
     *
     * @param string $value
     * @return $this
     */
    public function setDiscountType(string $value) : Coupon
    {
        $this->discountType = $value;

        return $this;
    }

    /**
     * Sets the coupon discount amount.
     *
     * @param float $value
     * @return $this
     */
    public function setDiscountAmount(float $value) : Coupon
    {
        $this->discountAmount = $value;

        return $this;
    }

    /**
     * Sets whether the coupon grants free shipping or not.
     *
     * @param bool $value
     * @return $this
     */
    public function setAllowsFreeShipping(bool $value) : Coupon
    {
        $this->allowsFreeShipping = $value;

        return $this;
    }

    /**
     * Sets the coupon expiration date.
     *
     * @param DateTime $value
     * @return $this
     */
    public function setExpiryDate(DateTime $value) : Coupon
    {
        $this->expiryDate = $value;

        return $this;
    }

    /**
     * Converts all model data properties to an array.
     *
     * @return array
     */
    public function toArray() : array
    {
        $data = $this->traitToArray();

        if ($expiryDate = $this->getExpiryDate()) {
            $data['expiryDate'] = $expiryDate->format('Y-m-d');
        }

        return $data;
    }
}
