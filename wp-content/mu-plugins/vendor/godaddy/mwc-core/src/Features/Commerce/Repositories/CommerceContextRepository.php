<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories;

use GoDaddy\WordPress\MWC\Common\Exceptions\WordPressDatabaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\DatabaseRepository;
use GoDaddy\WordPress\MWC\Common\Traits\IsSingletonTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Cache\CommerceContextCache;
use InvalidArgumentException;

/**
 * Repository for operations on the commerce contexts table, such as find or create a local commerce context ID
 * for a commerce storeId (uuid).
 *
 * The commerce context ID is an integer that can be used as an efficient foreign key on other tables.
 */
class CommerceContextRepository
{
    use IsSingletonTrait;

    /** @var string commerce context table name */
    public const CONTEXT_TABLE = 'godaddy_mwc_commerce_contexts';

    /**
     * Get context by the given store ID.
     *
     * @param string $storeId
     * @return int|null
     */
    public function getContextByStoreId(string $storeId) : ?int
    {
        $tableName = static::CONTEXT_TABLE;

        $row = DatabaseRepository::getRow(
            "SELECT id FROM {$tableName} WHERE gd_store_id = %s",
            [$storeId]
        );

        return TypeHelper::int(ArrayHelper::get($row, 'id'), 0) ?: null;
    }

    /**
     * Creates a new context for the given store ID.
     *
     * @param string $storeId
     * @return int
     * @throws InvalidArgumentException|WordPressDatabaseException
     */
    public function createContext(string $storeId) : int
    {
        if (empty($storeId)) {
            throw new InvalidArgumentException(__('Missing or invalid store ID', 'mwc-core'));
        }

        return DatabaseRepository::insert(static::CONTEXT_TABLE, [
            'gd_store_id' => $storeId,
        ]);
    }

    /**
     * Finds or creates a context for the given store ID.
     *
     * @param string $storeId
     * @return int
     * @throws InvalidArgumentException|WordPressDatabaseException
     */
    public function findOrCreateContext(string $storeId) : int
    {
        if ($contextId = $this->getContextByStoreId($storeId)) {
            return $contextId;
        }

        return $this->createContext($storeId);
    }

    /**
     * Finds or creates a context for the given store ID, with an added caching layer.
     *
     * @param string $storeId
     * @return int
     * @throws InvalidArgumentException|WordPressDatabaseException
     */
    public function findOrCreateContextWithCache(string $storeId) : int
    {
        $cache = CommerceContextCache::getInstance($storeId);
        if ($contextId = $cache->get()) {
            return TypeHelper::int($contextId, 0);
        } else {
            $contextId = $this->findOrCreateContext($storeId);

            $cache->set($contextId);

            return $contextId;
        }
    }
}
