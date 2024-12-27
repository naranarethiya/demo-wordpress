<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http;

use GoDaddy\WordPress\MWC\Common\Traits\CanConvertToArrayTrait;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

class WalletRequestCouponCodeObject
{
    use CanConvertToArrayTrait;
    use CanGetNewInstanceTrait;

    /** @var string|null */
    protected $code = null;

    /** @var string|null */
    protected $label = null;

    /**
     * Gets the code.
     *
     * @return string|null
     */
    public function getCode() : ?string
    {
        return $this->code;
    }

    /**
     * Sets the code.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setCode(string $value) : WalletRequestCouponCodeObject
    {
        $this->code = $value;

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
    public function setLabel(string $value) : WalletRequestCouponCodeObject
    {
        $this->label = $value;

        return $this;
    }
}
