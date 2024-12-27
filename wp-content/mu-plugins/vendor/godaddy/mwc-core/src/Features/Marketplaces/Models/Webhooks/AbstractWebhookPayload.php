<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Webhooks;

use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;

/**
 * Abstract class representing a webhook payload.
 */
abstract class AbstractWebhookPayload extends AbstractModel
{
    /** @var string type of event the payload is for */
    protected string $eventType;

    /** @var bool if this is the webhook event we are expecting it to be */
    protected bool $isExpectedEvent = false;

    /**
     * Gets the event type.
     *
     * @return string
     */
    public function getEventType() : string
    {
        return $this->eventType;
    }

    /**
     * Sets the event type.
     *
     * @param string $value
     * @return $this
     */
    public function setEventType(string $value) : AbstractWebhookPayload
    {
        $this->eventType = $value;

        return $this;
    }

    /**
     * Gets whether this is the expected webhook event.
     *
     * @return bool
     */
    public function getIsExpectedEvent() : bool
    {
        return $this->isExpectedEvent;
    }

    /**
     * Sets whether this is the expected webhook event.
     *
     * @param bool $value
     * @return $this
     */
    public function setIsExpectedEvent(bool $value) : AbstractWebhookPayload
    {
        $this->isExpectedEvent = $value;

        return $this;
    }

    /**
     * Determines whether this is the expected webhook event.
     *
     * @return bool
     */
    public function isExpectedEvent() : bool
    {
        return $this->isExpectedEvent;
    }
}
