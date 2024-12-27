<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce;

use GoDaddy\WordPress\MWC\Common\Database\AbstractDatabaseTableAction;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\DatabaseRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\CommerceContextRepository;

class CreateCommerceContextsTableAction extends AbstractDatabaseTableAction
{
    /** {@inheritDoc} */
    public static function getTableName() : string
    {
        return CommerceContextRepository::CONTEXT_TABLE;
    }

    /** {@inheritDoc} */
    protected static function getTableVersion() : int
    {
        return 20221230154100;
    }

    /** {@inheritdoc} */
    protected function createTable() : void
    {
        DatabaseRepository::createTable(
            static::getTableName(),
            [
                'id'          => ['SMALLINT', 'UNSIGNED', 'NOT NULL', 'AUTO_INCREMENT'],
                'gd_store_id' => ['CHAR(36)', 'NOT NULL'],
            ],
            [
                'PRIMARY KEY (id)',
                'UNIQUE KEY (gd_store_id)',
            ]
        );
    }
}
