<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Stripe\Models;

use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\AbstractPaymentMethod;

class AlternativePaymentMethod extends AbstractPaymentMethod
{
    use CanGetNewInstanceTrait;

    /** @var string type */
    protected $type;

    /** @var string last four of payment method */
    protected $lastFour;

    /**
     * Gets the type.
     *
     * @return string|null
     */
    public function getType() : ?string
    {
        return $this->type;
    }

    /**
     * Sets the type.
     *
     * @param string $value
     * @return $this
     */
    public function setType(string $value) : AlternativePaymentMethod
    {
        $this->type = $value;

        return $this;
    }

    /**
     * Gets the last four of the payment method.
     *
     * @return string|null
     */
    public function getLastFour() : ?string
    {
        return $this->lastFour;
    }

    /**
     * Sets the last four of the payment method.
     *
     * @param string $value
     * @return $this
     */
    public function setLastFour(string $value) : AlternativePaymentMethod
    {
        $this->lastFour = $value;

        return $this;
    }
}
