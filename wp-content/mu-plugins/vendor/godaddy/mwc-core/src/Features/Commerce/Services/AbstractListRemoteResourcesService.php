<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Services;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events\ProductsListedEvent;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\DataSources\WooCommerce\Builders\Contracts\ResourceAssociationBuilderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingRemoteIdsAfterLocalIdConversionException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Helpers\Contracts\ListRemoteResourcesCachingHelperContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Operations\Contracts\ListRemoteResourcesOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractResourceAssociation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\CachingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\ListRemoteResourcesServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Exceptions\CachingStrategyException;
use GoDaddy\WordPress\MWC\Core\Repositories\AbstractResourceMapRepository;

/**
 * Abstract class for listing remote resources services.
 */
abstract class AbstractListRemoteResourcesService implements ListRemoteResourcesServiceContract
{
    /** @var AbstractResourceMapRepository resource map repository to look up remote/local IDs */
    protected AbstractResourceMapRepository $resourceMapRepository;

    /** @var ListRemoteResourcesCachingHelperContract caching helper to identify caching capabilities and whether we need to execute the query */
    protected ListRemoteResourcesCachingHelperContract $listRemoteResourcesCachingHelper;

    /** @var CachingServiceContract caching service to get items from cache and set them */
    protected CachingServiceContract $cachingService;

    /** @var ResourceAssociationBuilderContract builds associations between remote resources and local IDs */
    protected ResourceAssociationBuilderContract $resourceAssociationBuilder;

    public function __construct(
        AbstractResourceMapRepository $resourceMapRepository,
        ListRemoteResourcesCachingHelperContract $listRemoteResourcesCachingHelper,
        CachingServiceContract $cachingService,
        ResourceAssociationBuilderContract $resourceAssociationBuilder
    ) {
        $this->resourceMapRepository = $resourceMapRepository;
        $this->listRemoteResourcesCachingHelper = $listRemoteResourcesCachingHelper;
        $this->cachingService = $cachingService;
        $this->resourceAssociationBuilder = $resourceAssociationBuilder;
    }

    /**
     * Executes a list query, and caches the results.
     *
     * @param ListRemoteResourcesOperationContract $operation
     * @return AbstractResourceAssociation[]
     * @throws CommerceExceptionContract|CachingStrategyException|BaseException|MissingRemoteIdsAfterLocalIdConversionException
     */
    public function list(ListRemoteResourcesOperationContract $operation) : array
    {
        $resources = [];

        $this->convertLocalEntitiesToRemote($operation);

        if ($this->listRemoteResourcesCachingHelper->canCacheOperation($operation)) {
            $resources = $this->listRemoteResourcesCachingHelper->getCachedResourcesFromOperation($operation);
        }

        if (! $this->listRemoteResourcesCachingHelper->isOperationFullyCached($operation, $resources)) {
            $resources = $this->executeListQuery($operation);

            $this->cachingService->setMany($resources);
        }

        $resourceAssociations = $this->resourceAssociationBuilder->build($resources);

        $this->maybeBroadcastListEvent($resourceAssociations);

        return $resourceAssociations;
    }

    /**
     * Executes the list query via the gateway.
     *
     * @param ListRemoteResourcesOperationContract $operation
     * @return AbstractDataObject[]
     */
    abstract protected function executeListQuery(ListRemoteResourcesOperationContract $operation) : array;

    /**
     * Converts local entities (e.g. IDs) to their remote equivalents, as necessary to execute the query.
     *
     * By default, this just calls {@see AbstractListRemoteResourcesService::convertLocalIdsToRemoteIds().
     * Child implementations may override this if they also want to convert other entities as well.
     *
     * @param ListRemoteResourcesOperationContract $operation
     * @return void
     * @throws MissingRemoteIdsAfterLocalIdConversionException|BaseException
     */
    protected function convertLocalEntitiesToRemote(ListRemoteResourcesOperationContract $operation) : void
    {
        $this->convertLocalIdsToRemoteIds($operation);
    }

    /**
     * Converts local resource IDs to their remote equivalents.
     *
     * @param ListRemoteResourcesOperationContract $operation
     * @return void
     * @throws MissingRemoteIdsAfterLocalIdConversionException
     */
    protected function convertLocalIdsToRemoteIds(ListRemoteResourcesOperationContract $operation) : void
    {
        if ($localIds = $operation->getLocalIds()) {
            $remoteIds = $this->resourceMapRepository->getMappingsByLocalIds($localIds)->getRemoteIds();

            try {
                $remoteIds = TypeHelper::arrayOfStrings(ArrayHelper::combine((array) $operation->getIds(), $remoteIds), false);
            } catch (BaseException $exception) {
                // ArrayHelper::combine() never throws if all parameters are arrays
                $remoteIds = [];
            }

            if (empty($remoteIds)) {
                throw MissingRemoteIdsAfterLocalIdConversionException::withDefaultMessage();
            }

            $operation->setIds(array_unique($remoteIds));
        }
    }

    /**
     * May broadcast a {@see ProductsListedEvent} event with the given association objects.
     *
     * The default implementation is empty, so this abstraction implementations must override this method to get it
     * functional.
     *
     * @codeCoverageIgnore there's nothing to be tested in this method
     *
     * @param AbstractResourceAssociation[] $resourceAssociations
     *
     * @return void
     */
    protected function maybeBroadcastListEvent(array $resourceAssociations) : void
    {
    }
}
