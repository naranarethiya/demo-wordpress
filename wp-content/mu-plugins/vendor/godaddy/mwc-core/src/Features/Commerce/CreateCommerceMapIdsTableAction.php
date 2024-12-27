<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce;

use GoDaddy\WordPress\MWC\Common\Database\AbstractDatabaseTableAction;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\DatabaseRepository;
use GoDaddy\WordPress\MWC\Core\Repositories\AbstractResourceMapRepository;

class CreateCommerceMapIdsTableAction extends AbstractDatabaseTableAction
{
    /** {@inheritDoc} */
    public static function getTableName() : string
    {
        return AbstractResourceMapRepository::MAP_IDS_TABLE;
    }

    /** {@inheritDoc} */
    protected static function getTableVersion() : int
    {
        return 20230323180000;
    }

    /** {@inheritdoc} */
    protected function createTable() : void
    {
        $resourceTypesTableName = CreateCommerceMapResourceTypesTableAction::getTableName();
        $contextTableName = CreateCommerceContextsTableAction::getTableName();

        DatabaseRepository::createTable(
            static::getTableName(),
            [
                'id'                  => ['BIGINT', 'UNSIGNED', 'NOT NULL', 'AUTO_INCREMENT'],
                'resource_type_id'    => ['SMALLINT', 'UNSIGNED', 'NOT NULL'],
                'commerce_context_id' => ['SMALLINT', 'UNSIGNED', 'NOT NULL'],
                'local_id'            => ['BIGINT', 'UNSIGNED', 'NOT NULL'],
                'commerce_id'         => ['VARCHAR(63)', 'COLLATE utf8mb4_bin', 'NOT NULL'],
            ],
            [
                'PRIMARY KEY (id)',
                'UNIQUE KEY (resource_type_id, local_id)',
                'INDEX (commerce_id)',
            ],
            [
                // cannot delete a row from godaddy_mwc_commerce_map_resource_types if there's a row that references it on this table
                "FOREIGN KEY (resource_type_id) REFERENCES {$resourceTypesTableName}(id) ON DELETE RESTRICT",
                "FOREIGN KEY (commerce_context_id) REFERENCES {$contextTableName}(id) ON DELETE RESTRICT",
            ]
        );
    }
}
