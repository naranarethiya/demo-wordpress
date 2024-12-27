<?php

namespace GoDaddy\WordPress\MWC\Common\Database\Cache;

use GoDaddy\WordPress\MWC\Common\Cache\Cache;
use GoDaddy\WordPress\MWC\Common\Cache\Traits\IsMemoryCacheOnlyTrait;

class TableExistenceCache extends Cache
{
    use IsMemoryCacheOnlyTrait;

    protected $keyPrefix = 'gd_table_exists_';

    /**
     * Creates a new cache instance associated with the given table name.
     *
     * @return static
     */
    public static function forTable(string $tableName)
    {
        return (new static())->key(strtolower($tableName));
    }

    /**
     * Final constructor to allow `new static()` to be called safely.
     *
     * The constructor is private because we want everyone to use the static constructor instead.
     */
    final private function __construct()
    {
    }
}
