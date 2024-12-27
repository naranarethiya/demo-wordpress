<?php

namespace GoDaddy\WordPress\MWC\Common\Events;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;

/**
 * Abstract webhook received event class.
 */
abstract class AbstractWebhookReceivedEvent implements EventContract
{
    /** @var array<mixed> */
    protected $headers;

    /** @var string */
    protected $payload;

    /**
     * Event constructor.
     *
     * @param array<mixed> $headers
     * @param string $payload
     */
    public function __construct(array $headers, string $payload)
    {
        $this->headers = $headers;
        $this->payload = $payload;
    }

    /**
     * Gets the headers.
     *
     * @return array<mixed>
     */
    public function getHeaders() : array
    {
        return $this->headers;
    }

    /**
     * Gets the payload.
     *
     * @return string
     */
    public function getPayload() : string
    {
        return $this->payload;
    }

    /**
     * Gets the JSON payload as a decoded array.
     *
     * @return array<mixed>
     */
    public function getPayloadDecoded() : array
    {
        return TypeHelper::array(json_decode($this->getPayload(), true), []);
    }
}
