<?php

namespace GoDaddy\WordPress\MWC\Core\Webhooks\DataObjects;

use DateTimeImmutable;
use GoDaddy\WordPress\MWC\Common\Http\IncomingRequest;

/**
 * Contains raw data from an incoming webhook request.
 */
class IncomingWebhookRequest extends IncomingRequest
{
    /** @var DateTimeImmutable|null timestamp the webhook occurred at.
     *
     * i.e. the time webhook was emitted as indicated by timestamp header in the webhook payload.
     */
    protected ?DateTimeImmutable $occurredAt = null;

    /** @var string unique identifier for this webhook */
    protected string $webhookId = '';

    /** @var string|null remote resource identifier for this webhook */
    protected ?string $remoteResourceId = null;

    /**
     * Gets the unique identifier for this webhook.
     *
     * @return string
     */
    public function getWebhookId() : string
    {
        return $this->webhookId;
    }

    /**
     * Sets the webhook identifier.
     *
     * @param string $value
     * @return $this
     */
    public function setWebhookId(string $value) : IncomingWebhookRequest
    {
        $this->webhookId = $value;

        return $this;
    }

    /**
     * Gets the timestamp for this webhook.
     *
     * @return DateTimeImmutable|null
     */
    public function getOccurredAt() : ?DateTimeImmutable
    {
        return $this->occurredAt;
    }

    /**
     * Sets the timestamp for this webhook.
     *
     * @param DateTimeImmutable|null $value
     * @return $this
     */
    public function setOccurredAt(?DateTimeImmutable $value) : IncomingWebhookRequest
    {
        $this->occurredAt = $value;

        return $this;
    }

    /**
     * Gets the unique identifier for this webhook.
     *
     * @return string|null
     */
    public function getRemoteResourceId() : ?string
    {
        return $this->remoteResourceId;
    }

    /**
     * Sets the remote resource identifier.
     *
     * @param string|null $value
     * @return $this
     */
    public function setRemoteResourceId(?string $value) : IncomingWebhookRequest
    {
        $this->remoteResourceId = $value;

        return $this;
    }
}
