<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\AbstractIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\InsertLocalResourceException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\InsertLocalResourceServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\MappingServiceContract;

/**
 * Abstract class to handle the insertion of a local resource, based on a corresponding remote resource.
 */
abstract class AbstractInsertLocalResourceService implements InsertLocalResourceServiceContract
{
    /** @var MappingServiceContract mapping service used to save the ID mapping */
    protected MappingServiceContract $mappingService;

    /** @var class-string<AbstractIntegration> name of the integration class */
    protected string $integrationClassName;

    public function __construct(MappingServiceContract $mappingService)
    {
        $this->mappingService = $mappingService;
    }

    /**
     * Inserts the a resource into the local database, corresponding to the provided remote resource.
     * Writes to the Commerce platform are temporarily disabled for the duration of the local insert.
     * Upon successful insertion, a new record is added to the mapping table to link the local entity to the remote equivalent.
     *
     * @param AbstractDataObject $remoteResource
     * @return int
     * @throws InsertLocalResourceException|CommerceExceptionContract
     */
    public function insert(AbstractDataObject $remoteResource) : int
    {
        $localResource = $this->integrationClassName::withoutWrites(fn () => $this->insertLocalResource($remoteResource));

        if (! $localResource || ! is_object($localResource)) {
            throw new InsertLocalResourceException('Failed to retrieve local resource during insertion.');
        }

        $this->mappingService->saveRemoteId($localResource, $this->getRemoteResourceId($remoteResource));

        return $this->getLocalResourceId($localResource);
    }

    /**
     * Inserts a local resource into the database.
     *
     * Exceptions should be thrown on failure.
     *
     * @param AbstractDataObject $remoteResource
     * @return object new local resource object -- should be the same type of object that can be passed through to {@see MappingServiceContract::saveRemoteId()}
     */
    abstract protected function insertLocalResource(AbstractDataObject $remoteResource) : object;

    /**
     * Gets the unique identifier of the remote resource, to save in the local mapping table.
     *
     * @param AbstractDataObject $remoteResource
     * @return non-empty-string
     */
    abstract protected function getRemoteResourceId(AbstractDataObject $remoteResource) : string;

    /**
     * Gets the unique identifier of the local resource, to save in the local mapping table.
     *
     * @param object $localResource
     * @return int
     */
    abstract protected function getLocalResourceId(object $localResource) : int;
}
