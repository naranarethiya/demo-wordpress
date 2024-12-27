<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingRemoteIdsAfterLocalIdConversionException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Operations\Contracts\ListRemoteResourcesOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractResourceAssociation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Exceptions\CachingStrategyException;

/**
 * Interface for facilitating "list" operations via the platform (i.e. querying for multiple resources) and returning resource associations.
 * This interface is not intended to execute the API request, just handle business/formatting logic before and after.
 */
interface ListRemoteResourcesServiceContract
{
    /**
     * Executes a list query and returns resources associated with their local IDs.
     *
     * @param ListRemoteResourcesOperationContract $operation
     * @return AbstractResourceAssociation[] association of the retrieved remote resources, linked to their local IDs
     * @throws CommerceExceptionContract|CachingStrategyException|BaseException|MissingRemoteIdsAfterLocalIdConversionException
     */
    public function list(ListRemoteResourcesOperationContract $operation) : array;
}
