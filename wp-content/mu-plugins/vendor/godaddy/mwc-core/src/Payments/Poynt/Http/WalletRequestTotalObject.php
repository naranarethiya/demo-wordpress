<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http;

use GoDaddy\WordPress\MWC\Common\Traits\CanConvertToArrayTrait;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

class WalletRequestTotalObject
{
    use CanConvertToArrayTrait;
    use CanGetNewInstanceTrait;

    /** @var string|null */
    protected $amount = null;

    /** @var string|null */
    protected $label = null;

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
    public function setAmount(float $value) : WalletRequestTotalObject
    {
        $this->amount = (string) $value;

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
    public function setLabel(string $value) : WalletRequestTotalObject
    {
        $this->label = $value;

        return $this;
    }
}
