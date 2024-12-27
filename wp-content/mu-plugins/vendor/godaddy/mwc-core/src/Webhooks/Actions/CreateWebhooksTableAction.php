<?php

namespace GoDaddy\WordPress\MWC\Core\Webhooks\Actions;

use GoDaddy\WordPress\MWC\Common\Database\AbstractDatabaseTableAction;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\DatabaseRepository;
use GoDaddy\WordPress\MWC\Core\Webhooks\Enums\WebhookStatuses;

/**
 * Creates a database table for storing received webhooks.
 */
class CreateWebhooksTableAction extends AbstractDatabaseTableAction
{
    /** {@inheritDoc} */
    public static function getTableName() : string
    {
        $wpdb = DatabaseRepository::instance();

        return $wpdb->prefix.'godaddy_mwc_received_webhooks';
    }

    /** {@inheritDoc} */
    protected static function getTableVersion() : int
    {
        return 20240619193000;
    }

    /** {@inheritDoc} */
    protected function createTable() : void
    {
        DatabaseRepository::createTable(
            static::getTableName(),
            [
                'id'                 => ['BIGINT', 'UNSIGNED', 'NOT NULL', 'AUTO_INCREMENT'],
                'namespace'          => ['VARCHAR(50)', 'COLLATE utf8mb4_bin', 'NOT NULL'], // Can increase later if needed.
                'webhook_id'         => ['VARCHAR(50)', 'COLLATE utf8mb4_bin', 'NOT NULL'], // Must be at least 36 characters to support a UUID.
                'remote_resource_id' => ['VARCHAR(63)', 'COLLATE utf8mb4_bin', 'DEFAULT NULL'],
                'payload'            => ['LONGTEXT', 'COLLATE utf8mb4_bin', 'NOT NULL'],
                'status'             => ['VARCHAR(50)', 'COLLATE utf8mb4_bin', 'DEFAULT "'.WebhookStatuses::Queued.'"'],
                'result'             => ['LONGTEXT', 'COLLATE utf8mb4_bin', 'DEFAULT NULL'],
                'received_at'        => ['DATETIME(6)', 'NOT NULL'],
                'occurred_at'        => ['DATETIME(6)', 'DEFAULT NULL'],
                'processed_at'       => ['DATETIME(6)', 'DEFAULT NULL'],
            ],
            [
                'PRIMARY KEY (id)',
                'UNIQUE KEY (webhook_id, namespace)', // ensure we don't allow duplicate webhooks for a given namespace
                'INDEX (remote_resource_id)',
            ]
        );
    }
}
