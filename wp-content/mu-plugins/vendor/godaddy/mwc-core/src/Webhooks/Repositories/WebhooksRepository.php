<?php

namespace GoDaddy\WordPress\MWC\Core\Webhooks\Repositories;

use DateTimeImmutable;
use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\WordPressDatabaseException;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\DatabaseRepository;
use GoDaddy\WordPress\MWC\Core\Webhooks\Actions\CreateWebhooksTableAction;
use GoDaddy\WordPress\MWC\Core\Webhooks\Adapters\WebhookAdapter;
use GoDaddy\WordPress\MWC\Core\Webhooks\DataObjects\Webhook;
use GoDaddy\WordPress\MWC\Core\Webhooks\Enums\WebhookStatuses;
use GoDaddy\WordPress\MWC\Core\Webhooks\Exceptions\InvalidWebhookRowIdException;

/**
 * Webhooks repository.
 */
class WebhooksRepository
{
    /**
     * Gets a webhook record.
     *
     * @param int $webhookRowId
     * @return Webhook|null
     */
    public function getWebhook(int $webhookRowId) : ?Webhook
    {
        $tableName = CreateWebhooksTableAction::getTableName();

        $row = DatabaseRepository::getRow(
            "SELECT * FROM {$tableName} WHERE id = %d",
            [$webhookRowId]
        );

        if (! $row) {
            return null;
        }

        return $this->adaptWebhook($row);
    }

    /**
     * Get a webhook record by webhook ID.
     *
     * @param string $webhookId
     * @return Webhook|null
     */
    public function getWebhookByWebhookId(string $webhookId) : ?Webhook
    {
        $tableName = CreateWebhooksTableAction::getTableName();

        $row = DatabaseRepository::getRow(
            "SELECT * FROM {$tableName} WHERE webhook_id = %s LIMIT 1",
            [$webhookId]
        );

        if (! $row) {
            return null;
        }

        return $this->adaptWebhook($row);
    }

    /**
     * Adds a webhook record.
     *
     * @param Webhook $webhook
     * @return int
     * @throws WordPressDatabaseException
     */
    public function addWebhook(Webhook $webhook) : int
    {
        return DatabaseRepository::insert(
            CreateWebhooksTableAction::getTableName(),
            WebhookAdapter::getNewInstance()->convertToDatabase($webhook)
        );
    }

    /**
     * Updates the processed_at field for a webhook.
     *
     * @param int $webhookRowId
     * @param string $status
     * @param string|null $result
     * @param DateTimeImmutable|null $processedAt If null, the current DateTimeImmutable instance is used.
     * @return void
     * @throws InvalidWebhookRowIdException
     * @throws WordPressDatabaseException
     */
    public function updateProcessedStatus(int $webhookRowId, string $status = '', ?string $result = null, ?DateTimeImmutable $processedAt = null)
    {
        $row = $this->getWebhook($webhookRowId);

        if (! $row) {
            throw new InvalidWebhookRowIdException();
        }

        DatabaseRepository::update(
            CreateWebhooksTableAction::getTableName(),
            [
                'status'       => $status,
                'result'       => $result,
                'processed_at' => $this->getProcessedAt($processedAt),
            ],
            [
                'id' => $webhookRowId,
            ]
        );
    }

    /**
     * Gets the processed_at field value.
     *
     * @param DateTimeImmutable|null $processedAt
     * @return string
     */
    protected function getProcessedAt(?DateTimeImmutable $processedAt) : string
    {
        return $processedAt ? $processedAt->format(WebhookAdapter::WEBHOOK_DATETIME_FORMAT) : (new DateTimeImmutable())->format(WebhookAdapter::WEBHOOK_DATETIME_FORMAT);
    }

    /**
     * Adapts a database row to a {@see Webhook} object.
     *
     * @param array<string, mixed> $row
     * @return Webhook
     */
    protected function adaptWebhook(array $row) : ?Webhook
    {
        try {
            return WebhookAdapter::getNewInstance()->convertFromDatabase($row);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get the newest (by `occurred_at`) webhook record for the given remote resource ID.
     *
     * @param string $remoteResourceId
     * @return Webhook|null
     */
    public function getLatestCompletedWebhookByResourceId(string $remoteResourceId) : ?Webhook
    {
        $tableName = CreateWebhooksTableAction::getTableName();

        $row = DatabaseRepository::getRow(
            "SELECT * FROM {$tableName} WHERE remote_resource_id = '%1\$s' AND status = '%2\$s' ORDER BY occurred_at DESC",
            [$remoteResourceId, WebhookStatuses::Completed]
        );

        if (! $row) {
            return null;
        }

        return $this->adaptWebhook($row);
    }
}
