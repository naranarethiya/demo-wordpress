<?php

namespace GoDaddy\WordPress\MWC\Common\Database;

use GoDaddy\WordPress\MWC\Common\Cache\Cache;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ConditionalComponentContract;
use GoDaddy\WordPress\MWC\Common\Database\Cache\TableExistenceCache;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Exceptions\WordPressDatabaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\DatabaseRepository;

abstract class AbstractDatabaseTableAction implements ConditionalComponentContract
{
    /**
     * Gets the name of the table.
     *
     * @return string
     */
    abstract public static function getTableName() : string;

    /**
     * Determines whether the table that this actions creates already exists.
     */
    public static function tableExists() : bool
    {
        return 'yes' === static::getTableExistenceCache()->remember(
            fn () => DatabaseRepository::tableExists(static::getTableName()) ? 'yes' : 'no'
        );
    }

    /**
     * Gets a cache instance used to store the result of the table existence check.
     */
    protected static function getTableExistenceCache() : Cache
    {
        return TableExistenceCache::forTable(static::getTableName());
    }

    /**
     * Gets the database table version (time of the latest version using YmdHis format).
     *
     * @return int
     */
    abstract protected static function getTableVersion() : int;

    /**
     * Gets the name of the database option that stores the version of the table.
     *
     * @return string
     */
    protected static function getTableVersionOptionName() : string
    {
        return static::getTableName().'_table_version';
    }

    /**
     * {@inheritDoc}
     */
    public static function shouldLoad() : bool
    {
        return static::getTableVersion() > TypeHelper::int(get_option(static::getTableVersionOptionName()), 0);
    }

    /**
     * Initializes the component.
     *
     * @throws WordPressDatabaseException
     * @throws BaseException
     */
    public function load() : void
    {
        $this->createTableIfNotExists();
    }

    /**
     * Creates a table if it doesn't exist.
     *
     * @throws WordPressDatabaseException
     * @throws BaseException
     */
    protected function createTableIfNotExists() : void
    {
        // we call DatabaseRepository::tableExists() directly to bypass the cache used in static::tableExists()
        if (DatabaseRepository::tableExists(static::getTableName())) {
            return;
        }

        $this->createTable();

        update_option(static::getTableVersionOptionName(), static::getTableVersion());

        static::getTableExistenceCache()->clear();
    }

    /**
     * Creates a database table.
     *
     * @throws WordPressDatabaseException
     * @throws BaseException
     */
    abstract protected function createTable() : void;
}
