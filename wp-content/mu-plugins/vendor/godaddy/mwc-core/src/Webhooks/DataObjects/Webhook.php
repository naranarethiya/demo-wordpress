<?php

namespace GoDaddy\WordPress\MWC\Core\Webhooks\DataObjects;

use DateTimeImmutable;
use GoDaddy\WordPress\MWC\Common\DataObjects\AbstractDataObject;
use GoDaddy\WordPress\MWC\Core\Webhooks\Enums\WebhookStatuses;

/**
 * Webhook DTO.
 */
class Webhook extends AbstractDataObject
{
    /** @var int|null the webhook auto-incremented id */
    public ?int $id = null;

    /** @var string the webhook namespace */
    public string $namespace;

    /** @var string the webhook identifier (typically a UUID) */
    public string $webhookId;

    /** @var string|null the webhook remote resource id */
    public ?string $remoteResourceId;

    /** @var string the webhook payload */
    public string $payload;

    /** @var string the webhook status {@see WebhookStatuses} */
    public string $status;

    /** @var string|null the webhook result */
    public ?string $result = null;

    /** @var DateTimeImmutable|null the webhook receipt time */
    public ?DateTimeImmutable $receivedAt = null;

    /** @var DateTimeImmutable|null the webhook timestamp time */
    public ?DateTimeImmutable $occurredAt = null;

    /** @var DateTimeImmutable|null the webhook processed time */
    public ?DateTimeImmutable $processedAt = null;

    /**
     * Subscription constructor.
     *
     * @param array{
     *     id?: int|null,
     *     namespace: string,
     *     webhookId: string,
     *     remoteResourceId?: string|null,
     *     payload: string,
     *     status: string,
     *     result?: string|null,
     *     receivedAt: DateTimeImmutable|null,
     *     occurredAt: DateTimeImmutable|null,
     *     processedAt: DateTimeImmutable|null,
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
