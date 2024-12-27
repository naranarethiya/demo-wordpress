<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\SkippedResources;

use GoDaddy\WordPress\MWC\Common\Exceptions\WordPressDatabaseException;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\DatabaseRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Enums\CommerceResourceTypes;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Enums\CommerceTableColumns;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Enums\CommerceTables;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\Traits\HasCommerceResourceTypeTrait;

/**
 * Abstract skipped resources repository class.
 * Each resource type {@see CommerceResourceTypes} is expected to create its own concrete implementation that extends
 * this class, and define the {@see static::$resourceType} property accordingly.
 */
abstract class AbstractSkippedResourcesRepository
{
    use HasCommerceResourceTypeTrait;

    /**
     * Gets a SQL query that can be used to select all `local_id` values from the table for a specific resource type ID.
     * e.g. SELECT local_id FROM godaddy_mwc_commerce_skipped_resources WHERE resource_type_id = 11.
     *
     * @return string
     */
    public static function getSkippedResourcesIdsQuery() : string
    {
        return '
        SELECT '.CommerceTableColumns::LocalId.'
        FROM '.CommerceTables::SkippedResources.'
        WHERE '.CommerceTableColumns::ResourceTypeId.' = %d
        ';
    }

    /**
     * Adds the provided local ID as a new skipped item for this resource.
     *
     * @param int $localId
     * @return void
     * @throws WordPressDatabaseException
     */
    public function add(int $localId) : void
    {
        DatabaseRepository::insert(CommerceTables::SkippedResources, [
            CommerceTableColumns::LocalId        => $localId,
            CommerceTableColumns::ResourceTypeId => $this->getResourceTypeId(),
        ]);
    }

    /**
     * Deletes all items for this resource.
     *
     * @return void
     * @throws WordPressDatabaseException
     */
    public function deleteAll() : void
    {
        DatabaseRepository::delete(
            CommerceTables::SkippedResources,
            [
                CommerceTableColumns::ResourceTypeId => $this->getResourceTypeId(),
            ],
            ['%d'],
        );
    }
}
