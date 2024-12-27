<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\AbstractCachingService as CommerceAbstractCachingService;

abstract class AbstractCachingService extends CommerceAbstractCachingService
{
    /** @var array<string, string[]> */
    protected static array $skippedResourceIds = [];

    /**
     * Gets the skipped resource IDs.
     *
     * @return string[]
     */
    public function getSkippedResourceIds() : array
    {
        return TypeHelper::arrayOfStrings(ArrayHelper::get(static::$skippedResourceIds, $this->resourceType, []));
    }

    /**
     * Adds the given resource IDs to the list of skipped resource IDs.
     *
     * @param string[] $resourceIds
     *
     * @return $this
     */
    public function addSkippedResourceIds(array $resourceIds) : AbstractCachingService
    {
        ArrayHelper::set(static::$skippedResourceIds, $this->resourceType, array_unique(array_merge($this->getSkippedResourceIds(), $resourceIds)));

        return $this;
    }
}
