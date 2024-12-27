<?php

namespace GoDaddy\WordPress\MWC\Common\Helpers\WebhookValidation\DataObjects;

use GoDaddy\WordPress\MWC\Common\DataObjects\AbstractDataObject;
use GoDaddy\WordPress\MWC\Common\Helpers\WebhookValidation\WebhookValidationStrategy;

/**
 * A data object to contain standard webhook headers.
 *
 * {@see WebhookValidationStrategy}
 */
class WebhookHeaders extends AbstractDataObject
{
    /** @var string Unique message ID */
    public string $messageId;

    /** @var string The Timestamp in ISO 8601 format */
    public string $timestamp;

    /** @var string[] And array of signatures */
    public array $signatures;

    /**
     * @param array{
     *     messageId: string,
     *     timestamp: string,
     *     signatures: string[],
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
