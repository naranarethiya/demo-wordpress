<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers\BatchRequestHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingRemoteIdsAfterLocalIdConversionException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractResourceAssociation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Exceptions\CachingStrategyException;

/**
 * Abstract class for listing resources by ID in batches.
 *
 * The maximum number of resources we can request by UUID at one time is `100`, otherwise the URL becomes too long.
 * This service class batches up the requests until we've retrieved all the requested resources.
 */
abstract class AbstractBatchListResourcesByLocalIdService
{
    /**
     * Gets the maximum number of products we should query in one API request.
     *
     * @return positive-int
     */
    protected function getMaximumResourcesPerRequest() : int
    {
        return BatchRequestHelper::getMaxIdsPerRequest();
    }

    /**
     * Lists resources from the API in batches, using the local IDs.
     *
     * @param int[] $localIds
     * @return AbstractResourceAssociation[] results from all batches merged into one array
     */
    public function batchListByLocalIds(array $localIds) : array
    {
        $resourceAssociations = [];

        foreach (array_chunk($localIds, $this->getMaximumResourcesPerRequest()) as $localIdsBatch) {
            try {
                /** @var AbstractResourceAssociation[] $resourceAssociations */
                $resourceAssociations = ArrayHelper::combine($resourceAssociations, $this->listBatch($localIdsBatch));
            } catch(MissingRemoteIdsAfterLocalIdConversionException $exception) {
                // we don't need to report this exception to Sentry
            } catch(Exception|CommerceExceptionContract $exception) {
                SentryException::getNewInstance($exception->getMessage(), $exception);
            }
        }

        return $resourceAssociations;
    }

    /**
     * Lists a batch of resources by local IDs.
     *
     * @param int[] $localIds
     * @return AbstractResourceAssociation[]
     * @throws MissingRemoteIdsAfterLocalIdConversionException|BaseException|CommerceExceptionContract|CachingStrategyException
     */
    abstract protected function listBatch(array $localIds) : array;
}
