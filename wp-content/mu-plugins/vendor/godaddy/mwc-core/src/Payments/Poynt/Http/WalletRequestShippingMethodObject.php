<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http;

use GoDaddy\WordPress\MWC\Common\Traits\CanConvertToArrayTrait;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

class WalletRequestShippingMethodObject
{
    use CanConvertToArrayTrait;
    use CanGetNewInstanceTrait;

    /** @var string|null */
    protected $id = null;

    /** @var string|null */
    protected $label = null;

    /** @var string|null */
    protected $amount = null;

    /** @var string shipping method detail (description) - an empty string by default to accommodate Apple Pay UI */
    protected $detail = '';

    /**
     * Gets the ID.
     *
     * @return string|null
     */
    public function getId() : ?string
    {
        return $this->id;
    }

    /**
     * Sets the ID.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setId(string $value) : WalletRequestShippingMethodObject
    {
        $this->id = $value;

        return $this;
    }

    /**
     * Gets the label.
     *
     * @return string|null
     */
    public function getLabel() : ?string
    {
        return $this->label;
    }

    /**
     * Sets the label.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setLabel(string $value) : WalletRequestShippingMethodObject
    {
        $this->label = $value;

        return $this;
    }

    /**
     * Gets the amount.
     *
     * @return string|null
     */
    public function getAmount() : ?string
    {
        return $this->amount;
    }

    /**
     * Sets the amount.
     *
     * @param float $value
     *
     * @return $this
     */
    public function setAmount(float $value) : WalletRequestShippingMethodObject
    {
        $this->amount = (string) $value;

        return $this;
    }

    /**
     * Gets the detail (description).
     *
     * @return string
     */
    public function getDetail() : string
    {
        return $this->detail;
    }

    /**
     * Sets the detail.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setDetail(string $value) : WalletRequestShippingMethodObject
    {
        $this->detail = $value;

        return $this;
    }
}
