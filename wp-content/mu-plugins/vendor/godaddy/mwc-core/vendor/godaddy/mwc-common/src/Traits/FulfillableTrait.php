<?php

namespace GoDaddy\WordPress\MWC\Common\Traits;

use GoDaddy\WordPress\MWC\Common\Contracts\FulfillmentStatusContract;

/**
 * A trait for objects that handle fulfillment.
 */
trait FulfillableTrait
{
    /** @var FulfillmentStatusContract fulfillment status */
    protected $fulfillmentStatus;

    protected ?string $fulfillmentChannelId = null;

    /** @var bool whether the represented entity needs shipping or not */
    protected $needsShipping;

    /**
     * Gets the fulfillment status.
     *
     * @return FulfillmentStatusContract|null
     */
    public function getFulfillmentStatus()
    {
        return $this->fulfillmentStatus;
    }

    /**
     * Sets the fulfillment status.
     *
     * @param FulfillmentStatusContract $fulfillmentStatus
     * @return $this
     */
    public function setFulfillmentStatus(FulfillmentStatusContract $fulfillmentStatus)
    {
        $this->fulfillmentStatus = $fulfillmentStatus;

        return $this;
    }

    /**
     * Gets the fulfillment channel ID.
     *
     * @return string|null
     */
    public function getFulfillmentChannelId() : ?string
    {
        return $this->fulfillmentChannelId;
    }

    /**
     * Sets the fulfillment channel ID.
     *
     * @return $this
     */
    public function setFulfillmentChannelId(string $value)
    {
        $this->fulfillmentChannelId = $value;

        return $this;
    }

    /**
     * Determines whether the represented entity needs shipping or not.
     *
     * @return bool
     */
    public function getNeedsShipping() : bool
    {
        return $this->needsShipping ?: false;
    }

    /**
     * Sets the "needs shipping" property.
     *
     * @param bool $needsShipping
     * @return $this
     */
    public function setNeedsShipping(bool $needsShipping)
    {
        $this->needsShipping = $needsShipping;

        return $this;
    }
}
