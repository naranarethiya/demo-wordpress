<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Actions;

use GoDaddy\WordPress\MWC\Common\Database\AbstractDatabaseTableAction;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\DatabaseRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Enums\CommerceTables;

/**
 * Creates a database table for storing active Commerce webhook subscriptions.
 */
class CreateCommerceWebhookSubscriptionsTableAction extends AbstractDatabaseTableAction
{
    /** {@inheritDoc} */
    public static function getTableName() : string
    {
        $wpdb = DatabaseRepository::instance();

        return $wpdb->prefix.CommerceTables::WebhookSubscriptions;
    }

    /** {@inheritDoc} */
    protected static function getTableVersion() : int
    {
        return 20240604114400;
    }

    /** {@inheritDoc} */
    protected function createTable() : void
    {
        $contextTableName = CommerceTables::Contexts;

        DatabaseRepository::createTable(
            static::getTableName(),
            [
                'id'                  => ['BIGINT', 'UNSIGNED', 'NOT NULL', 'AUTO_INCREMENT'],
                'commerce_context_id' => ['SMALLINT', 'UNSIGNED', 'NOT NULL'],
                'subscription_id'     => ['VARCHAR(63)', 'COLLATE utf8mb4_bin', 'NOT NULL'],
                'name'                => ['TEXT', 'NOT NULL'],
                'description'         => ['TEXT', 'DEFAULT NULL'],
                'event_types'         => ['TEXT', 'NOT NULL'],
                'delivery_url'        => ['TEXT', 'NOT NULL'],
                'is_enabled'          => ['TINYINT(1)', 'NOT NULL'],
                'secret'              => ['VARCHAR(30)', 'COLLATE utf8mb4_bin', 'NOT NULL'],
                'created_at'          => ['DATETIME', 'NOT NULL', 'DEFAULT CURRENT_TIMESTAMP'],
                'updated_at'          => ['DATETIME', 'NOT NULL', 'DEFAULT CURRENT_TIMESTAMP'],
            ],
            [
                'PRIMARY KEY (id)',
                'UNIQUE KEY (subscription_id)',
                'INDEX (commerce_context_id)',
            ],
            [
                "FOREIGN KEY (commerce_context_id) REFERENCES {$contextTableName}(id) ON DELETE RESTRICT",
            ]
        );
    }
}
