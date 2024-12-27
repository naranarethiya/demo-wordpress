<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce;

use GoDaddy\WordPress\MWC\Common\Database\AbstractDatabaseTableAction;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\DatabaseRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Enums\CommerceTables;
use GoDaddy\WordPress\MWC\Core\Repositories\AbstractResourceMapRepository;

/**
 * This table will store the last time a resource type was updated.
 *
 * This is helpful to compare the updatedAt value of a remote object with the value stored in this table.
 * If the remote object has a more recent updatedAt value, then we know we might need to purge the local cache for that resource.
 * When doing that, we should also update the value in this table.
 *
 * @see AbstractResourceMapRepository::getResourceUpdatedAt()
 * @see AbstractResourceMapRepository::updateResourceUpdatedAt()
 */
class CreateCommerceResourceUpdatesTableAction extends AbstractDatabaseTableAction
{
    /** {@inheritDoc} */
    public static function getTableName() : string
    {
        return CommerceTables::ResourceUpdates;
    }

    /** {@inheritDoc} */
    protected static function getTableVersion() : int
    {
        return 20230707000600;
    }

    /** {@inheritdoc} */
    protected function createTable() : void
    {
        $resourceTypesTableName = CreateCommerceMapResourceTypesTableAction::getTableName();

        DatabaseRepository::createTable(
            static::getTableName(),
            [
                'id'                => ['BIGINT', 'UNSIGNED', 'NOT NULL', 'AUTO_INCREMENT'],
                'resource_type_id'  => ['SMALLINT', 'UNSIGNED', 'NOT NULL'],
                'commerce_id'       => ['VARCHAR(63)', 'COLLATE utf8mb4_bin', 'NOT NULL'],
                'remote_updated_at' => ['DATETIME', 'NOT NULL', 'DEFAULT CURRENT_TIMESTAMP'],
            ],
            [
                'PRIMARY KEY (id)',
                'UNIQUE KEY (commerce_id, resource_type_id)',
            ],
            [
                // cannot delete a row from godaddy_mwc_commerce_map_resource_types if there's a row that references it on this table
                "FOREIGN KEY (resource_type_id) REFERENCES {$resourceTypesTableName}(id) ON DELETE RESTRICT",
            ],
        );
    }
}
