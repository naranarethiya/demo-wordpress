<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce;

use GoDaddy\WordPress\MWC\Common\Database\AbstractDatabaseTableAction;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\DatabaseRepository;
use GoDaddy\WordPress\MWC\Core\Repositories\AbstractResourceMapRepository;

class CreateCommerceMapResourceTypesTableAction extends AbstractDatabaseTableAction
{
    /** {@inheritDoc} */
    public static function getTableName() : string
    {
        return AbstractResourceMapRepository::RESOURCE_TYPES_TABLE;
    }

    /** {@inheritDoc} */
    protected static function getTableVersion() : int
    {
        return 20221214102100;
    }

    /** {@inheritdoc} */
    protected function createTable() : void
    {
        DatabaseRepository::createTable(
            static::getTableName(),
            [
                'id'   => ['SMALLINT', 'UNSIGNED', 'NOT NULL', 'AUTO_INCREMENT'],
                'name' => ['VARCHAR(255)', 'NOT NULL'],
            ],
            [
                'PRIMARY KEY (id)',
                'UNIQUE KEY (name)',
            ]
        );
    }
}
