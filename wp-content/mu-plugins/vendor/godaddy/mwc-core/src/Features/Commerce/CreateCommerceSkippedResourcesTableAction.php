<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce;

use GoDaddy\WordPress\MWC\Common\Database\AbstractDatabaseTableAction;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\DatabaseRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Enums\CommerceTables;

/**
 * Creates a table for storing resources that are skipped during a Commerce backfill.
 */
class CreateCommerceSkippedResourcesTableAction extends AbstractDatabaseTableAction
{
    /** {@inheritDoc} */
    public static function getTableName() : string
    {
        return CommerceTables::SkippedResources;
    }

    /** {@inheritDoc} */
    protected static function getTableVersion() : int
    {
        return 20231002143700;
    }

    /** {@inheritdoc} */
    protected function createTable() : void
    {
        $resourceTypesTableName = CreateCommerceMapResourceTypesTableAction::getTableName();

        DatabaseRepository::createTable(
            static::getTableName(),
            [
                'id'               => ['BIGINT', 'UNSIGNED', 'NOT NULL', 'AUTO_INCREMENT'],
                'resource_type_id' => ['SMALLINT', 'UNSIGNED', 'NOT NULL'],
                'local_id'         => ['BIGINT', 'UNSIGNED', 'NOT NULL'],
            ],
            [
                'PRIMARY KEY (id)',
                'UNIQUE KEY (resource_type_id, local_id)',
            ],
            [
                // cannot delete a row from godaddy_mwc_commerce_map_resource_types if there's a row that references it on this table
                "FOREIGN KEY (resource_type_id) REFERENCES {$resourceTypesTableName}(id) ON DELETE RESTRICT",
            ],
        );
    }
}
