<?php

namespace GoDaddy\WordPress\MWC\Core\Webhooks\Adapters;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\AdapterException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Webhooks\DataObjects\IncomingWebhookRequest;
use GoDaddy\WordPress\MWC\Core\Webhooks\DataObjects\Webhook;
use GoDaddy\WordPress\MWC\Core\Webhooks\Enums\WebhookStatuses;

class WebhookAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var string The DateTime format */
    public const WEBHOOK_DATETIME_FORMAT = 'Y-m-d H:i:s.u';

    /**
     * Converts an incoming webhook request object into a Webhook DTO for database storage.
     *
     * @param IncomingWebhookRequest|null $request
     * @param string|null $namespace
     * @return Webhook
     * @throws AdapterException
     * @throws Exception
     */
    public function convertFromSource(?IncomingWebhookRequest $request = null, ?string $namespace = null) : Webhook
    {
        if (! $request || ! $namespace) {
            throw new AdapterException('Missing required request or namespace.');
        }

        return new Webhook([
            'namespace'        => $namespace,
            'webhookId'        => $this->getOrGenerateWebhookId($request),
            'payload'          => $request->getBody() ?: '',
            'status'           => WebhookStatuses::Queued,
            'result'           => null,
            'receivedAt'       => new DateTimeImmutable('now', new DateTimeZone('UTC')),
            'occurredAt'       => $request->getOccurredAt(),
            'processedAt'      => null,
            'remoteResourceId' => $request->getRemoteResourceId(),
        ]);
    }

    /**
     * Gets the webhook identifier from the request. If none is set, then a UUID is generated.
     *
     * @param IncomingWebhookRequest $request
     * @return string
     */
    protected function getOrGenerateWebhookId(IncomingWebhookRequest $request) : string
    {
        if ($webhookId = $request->getWebhookId()) {
            return $webhookId;
        }

        // otherwise we need to generate one
        return StringHelper::generateUuid4();
    }

    public function convertToSource()
    {
        // no-op
    }

    /**
     * Converts a Webhook DTO to a database array.
     *
     * @param Webhook $webhook
     * @return array<string,string|null>
     */
    public function convertToDatabase(Webhook $webhook) : array
    {
        return [
            'namespace'          => $webhook->namespace,
            'webhook_id'         => $webhook->webhookId,
            'remote_resource_id' => $webhook->remoteResourceId,
            'payload'            => $webhook->payload,
            'status'             => $webhook->status,
            'result'             => $webhook->result,
            'received_at'        => $webhook->receivedAt ? $webhook->receivedAt->format(self::WEBHOOK_DATETIME_FORMAT) : null,
            'occurred_at'        => $webhook->occurredAt ? $webhook->occurredAt->format(self::WEBHOOK_DATETIME_FORMAT) : null,
            'processed_at'       => $webhook->processedAt ? $webhook->processedAt->format(self::WEBHOOK_DATETIME_FORMAT) : null,
        ];
    }

    /**
     * Adapts a database row to a Webhook object.
     *
     * @param array<string, mixed> $row
     * @return Webhook
     * @throws Exception
     */
    public function convertFromDatabase(array $row) : Webhook
    {
        return Webhook::getNewInstance([
            'id'               => ArrayHelper::getIntValueForKey($row, 'id'),
            'namespace'        => ArrayHelper::getStringValueForKey($row, 'namespace'),
            'webhookId'        => ArrayHelper::getStringValueForKey($row, 'webhook_id'),
            'remoteResourceId' => ArrayHelper::getStringValueForKey($row, 'remote_resource_id'),
            'payload'          => ArrayHelper::getStringValueForKey($row, 'payload'),
            'status'           => ArrayHelper::getStringValueForKey($row, 'status'),
            'result'           => TypeHelper::stringOrNull(ArrayHelper::get($row, 'result')),
            'receivedAt'       => ($receivedAt = TypeHelper::stringOrNull(ArrayHelper::get($row, 'received_at'))) ? $this->getDateTimeImmutableFromString($receivedAt) : null,
            'occurredAt'       => ($occurredAt = TypeHelper::stringOrNull(ArrayHelper::get($row, 'occurred_at'))) ? $this->getDateTimeImmutableFromString($occurredAt) : null,
            'processedAt'      => ($processedAt = TypeHelper::stringOrNull(ArrayHelper::get($row, 'processed_at'))) ? $this->getDateTimeImmutableFromString($processedAt) : null,
        ]);
    }

    /**
     * Converts a DateTime string to a DateTimeImmutable object.
     *
     * @param string $dateTime
     * @return DateTimeImmutable
     * @throws Exception
     */
    protected function getDateTimeImmutableFromString(string $dateTime) : DateTimeImmutable
    {
        return new DateTimeImmutable($dateTime, new DateTimeZone('UTC'));
    }
}
