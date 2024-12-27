<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http;

use GoDaddy\WordPress\MWC\Common\Traits\CanConvertToArrayTrait;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

class WalletRequestLineItemObject
{
    use CanConvertToArrayTrait;
    use CanGetNewInstanceTrait;

    /** @var string|null */
    protected $label = null;

    /** @var string|null */
    protected $amount = null;

    /** @var bool */
    protected $isPending = false;

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
    public function setLabel(string $value) : WalletRequestLineItemObject
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
    public function setAmount(float $value) : WalletRequestLineItemObject
    {
        $this->amount = (string) $value;

        return $this;
    }

    /**
     * Gets the pending flag value.
     *
     * @return bool
     */
    public function getIsPending() : bool
    {
        return $this->isPending;
    }

    /**
     * Sets the pending flag value.
     *
     * @param bool $value
     *
     * @return $this
     */
    public function setIsPending(bool $value) : WalletRequestLineItemObject
    {
        $this->isPending = $value;

        return $this;
    }
}
