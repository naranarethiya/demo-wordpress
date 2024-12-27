<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http;

use GoDaddy\WordPress\MWC\Common\Traits\CanConvertToArrayTrait;

class WalletRequestObject
{
    use CanConvertToArrayTrait;

    /** @var WalletRequestTotalObject|null */
    protected $total = null;

    /** @var string|null */
    protected $country = null;

    /** @var string|null */
    protected $currency = null;

    /** @var string|null */
    protected $merchantName = null;

    /** @var string|null */
    protected $shippingType = null;

    /** @var WalletRequestShippingMethodObject[] */
    protected $shippingMethods = [];

    /** @var WalletRequestLineItemObject[] */
    protected $lineItems = [];

    /** @var bool */
    protected $requireEmail = false;

    /** @var bool */
    protected $requirePhone = false;

    /** @var bool */
    protected $requireShippingAddress = false;

    /** @var bool */
    protected $supportCouponCode = false;

    /** @var WalletRequestCouponCodeObject|null */
    protected $couponCode = null;

    /** @var array<string, bool> */
    protected $disableWallets = [];

    /**
     * Gets the total.
     *
     * @return WalletRequestTotalObject
     */
    public function getTotal() : WalletRequestTotalObject
    {
        return $this->total ?? WalletRequestTotalObject::getNewInstance();
    }

    /**
     * Sets the total.
     *
     * @param WalletRequestTotalObject $value
     *
     * @return $this
     */
    public function setTotal(WalletRequestTotalObject $value) : WalletRequestObject
    {
        $this->total = $value;

        return $this;
    }

    /**
     * Gets the country.
     *
     * @return string|null
     */
    public function getCountry() : ?string
    {
        return $this->country;
    }

    /**
     * Sets the country.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setCountry(string $value) : WalletRequestObject
    {
        $this->country = $value;

        return $this;
    }

    /**
     * Gets the currency.
     *
     * @return string|null
     */
    public function getCurrency() : ?string
    {
        return $this->currency;
    }

    /**
     * Sets the currency.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setCurrency(string $value) : WalletRequestObject
    {
        $this->currency = $value;

        return $this;
    }

    /**
     * Gets the merchant name.
     *
     * @return string|null
     */
    public function getMerchantName() : ?string
    {
        return $this->merchantName;
    }

    /**
     * Sets the merchant name.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setMerchantName(string $value) : WalletRequestObject
    {
        $this->merchantName = $value;

        return $this;
    }

    /**
     * Gets the shipping type.
     *
     * @return string|null
     */
    public function getShippingType() : ?string
    {
        return $this->shippingType;
    }

    /**
     * Sets the shipping type.
     *
     * @param string|null $value
     *
     * @return $this
     */
    public function setShippingType(?string $value) : WalletRequestObject
    {
        $this->shippingType = $value;

        return $this;
    }

    /**
     * Gets the shipping methods.
     *
     * @return WalletRequestShippingMethodObject[]
     */
    public function getShippingMethods() : array
    {
        return $this->shippingMethods;
    }

    /**
     * Sets the shipping methods.
     *
     * @param WalletRequestShippingMethodObject[] $value
     *
     * @return $this
     */
    public function setShippingMethods(array $value) : WalletRequestObject
    {
        $this->shippingMethods = $value;

        return $this;
    }

    /**
     * Gets the line items.
     *
     * @return WalletRequestLineItemObject[]
     */
    public function getLineItems() : array
    {
        return $this->lineItems;
    }

    /**
     * Sets the line items.
     *
     * @param WalletRequestLineItemObject[] $value
     *
     * @return $this
     */
    public function setLineItems(array $value) : WalletRequestObject
    {
        $this->lineItems = $value;

        return $this;
    }

    /**
     * Gets require email.
     *
     * @return bool
     */
    public function getRequireEmail() : bool
    {
        return $this->requireEmail;
    }

    /**
     * Sets require email.
     *
     * @param bool $value
     *
     * @return $this
     */
    public function setRequireEmail(bool $value) : WalletRequestObject
    {
        $this->requireEmail = $value;

        return $this;
    }

    /**
     * Gets require phone.
     *
     * @return bool
     */
    public function getRequirePhone() : bool
    {
        return $this->requirePhone;
    }

    /**
     * Sets require phone.
     *
     * @param bool $value
     *
     * @return $this
     */
    public function setRequirePhone(bool $value) : WalletRequestObject
    {
        $this->requirePhone = $value;

        return $this;
    }

    /**
     * Gets require shipping address.
     *
     * @return bool
     */
    public function getRequireShippingAddress() : bool
    {
        return $this->requireShippingAddress;
    }

    /**
     * Sets require shipping address.
     *
     * @param bool $value
     *
     * @return $this
     */
    public function setRequireShippingAddress(bool $value) : WalletRequestObject
    {
        $this->requireShippingAddress = $value;

        return $this;
    }

    /**
     * Gets support coupon code.
     *
     * @return bool
     */
    public function getSupportCouponCode() : bool
    {
        return $this->supportCouponCode;
    }

    /**
     * Sets support coupon code.
     *
     * @param bool $value
     *
     * @return $this
     */
    public function setSupportCouponCode(bool $value) : WalletRequestObject
    {
        $this->supportCouponCode = $value;

        return $this;
    }

    /**
     * Gets the coupon code.
     *
     * @return WalletRequestCouponCodeObject
     */
    public function getCouponCode() : WalletRequestCouponCodeObject
    {
        return $this->couponCode ?? WalletRequestCouponCodeObject::getNewInstance();
    }

    /**
     * Sets the coupon code.
     *
     * @param WalletRequestCouponCodeObject $value
     *
     * @return $this
     */
    public function setCouponCode(WalletRequestCouponCodeObject $value) : WalletRequestObject
    {
        $this->couponCode = $value;

        return $this;
    }

    /**
     * Gets disable wallets.
     *
     * @return array<mixed>
     */
    public function getDisableWallets() : array
    {
        return $this->disableWallets;
    }

    /**
     * Sets disable wallets.
     *
     * @param array<string, bool> $value
     *
     * @return $this
     */
    public function setDisableWallets(array $value) : WalletRequestObject
    {
        $this->disableWallets = $value;

        return $this;
    }
}
