<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\DataSources\WooCommerce\Builders;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Exceptions\WordPressDatabaseException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\DataSources\WooCommerce\Builders\Contracts\ResourceAssociationBuilderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\InsertLocalResourceException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\ResourceMapCollection;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractResourceAssociation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\InsertLocalResourceServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\CanBroadcastResourceEventsTrait;
use GoDaddy\WordPress\MWC\Core\Repositories\AbstractResourceMapRepository;

/**
 * Base class to build associations between remote and local WooCommerce resources.
 */
abstract class AbstractResourceAssociationBuilder implements ResourceAssociationBuilderContract
{
    use CanBroadcastResourceEventsTrait;

    /** @var AbstractResourceMapRepository resource map repository to look up remote/local IDs */
    protected AbstractResourceMapRepository $resourceMapRepository;

    /** @var InsertLocalResourceServiceContract service to insert resources into the local database */
    protected InsertLocalResourceServiceContract $insertLocalResourceService;

    /** @var string name of the "ID" property on the remote object DTO {@see AbstractDataObject} -- should be the value stored in the resource map table */
    protected string $remoteObjectIdProperty;

    /**
     * Constructor.
     *
     * @param AbstractResourceMapRepository $resourceMapRepository
     * @param InsertLocalResourceServiceContract $insertLocalResourceService
     */
    public function __construct(AbstractResourceMapRepository $resourceMapRepository, InsertLocalResourceServiceContract $insertLocalResourceService)
    {
        $this->resourceMapRepository = $resourceMapRepository;
        $this->insertLocalResourceService = $insertLocalResourceService;
    }

    /**
     * {@inheritDoc}
     */
    public function build(array $resources) : array
    {
        $remoteResourceIds = array_filter(array_column($resources, $this->remoteObjectIdProperty));
        $resourceAssociations = [];
        $insertedResourceAssociations = [];

        if (empty($remoteResourceIds)) {
            return $resourceAssociations;
        }

        $localAndRemoteIds = $this->getLocalAndRemoteIds($remoteResourceIds);

        $wcLogger = null;

        foreach ($resources as $resource) {
            try {
                if ($localId = $this->getRemoteResourceLocalId($resource, $localAndRemoteIds)) {
                    $resourceAssociations[] = $this->makeResourceAssociation([
                        'localId'        => $localId,
                        'remoteResource' => $resource,
                    ]);

                    continue;
                }

                if ($localId = $this->maybeInsertLocalId($resource)) {
                    $createdResourceAssociation = $this->makeResourceAssociation([
                        'localId'        => $localId,
                        'remoteResource' => $resource,
                    ]);

                    $insertedResourceAssociations[] = $createdResourceAssociation;
                    $resourceAssociations[] = $createdResourceAssociation;
                }
            } catch (InsertLocalResourceException $e) {
                /*
                 * An InsertLocalResourceException is thrown when the remote product cannot be inserted into the
                 * local database {@see AbstractInsertLocalResource::insert()}. This most frequently occurs when the
                 * remote data is invalid or conflicts with existing data in the local database. For example, if the
                 * remote product had a SKU that is already in use by a local product in the local DB.
                 *
                 * In general, an InsertLocalResourceException would not represent an error in the platform code and is
                 * more likely to be caused by an error with customer data, or in some cases a database issue.
                 *
                 * For that reason, we will not send the exception to Sentry or throw but instead log the exception to
                 * a WC log file to assist in debugging issues the customer may be experiencing with their data.
                 */
                $wcLogger = $wcLogger ?? wc_get_logger();
                $wcLogger->info(
                    $e->getMessage(),
                    [
                        'source' => 'godaddy-mwc',
                    ]);
            } catch(Exception $exception) {
                // this resource will not be included in the final array
                new SentryException(sprintf('Failed to associate remote %1$s with local entity: %2$s', $this->resourceMapRepository->getResourceType(), $exception->getMessage()), $exception);
            }
        }

        $this->maybeBroadcastInsertedResourcesEvent($insertedResourceAssociations);

        return $resourceAssociations;
    }

    /**
     * Gets the full mapping database rows (containing both local and remote ID) for the given remote resource IDs.
     *
     * @param string[] $remoteResourceIds
     *
     * @return ResourceMapCollection
     */
    protected function getLocalAndRemoteIds(array $remoteResourceIds) : ResourceMapCollection
    {
        return $this->resourceMapRepository->getMappingsByRemoteIds(array_map('strval', $remoteResourceIds));
    }

    /**
     * Makes a new instance of an {@see AbstractResourceAssociation} object from the provided data.
     *
     * @param array<string, AbstractDataObject|int> $data
     * @return AbstractResourceAssociation
     */
    abstract protected function makeResourceAssociation(array $data) : AbstractResourceAssociation;

    /**
     * Gets the local ID of the provided remote resource. If no local ID exists, return null.
     *
     * @param AbstractDataObject $resource
     * @param ResourceMapCollection $resourceMapCollection
     * @return int|null
     */
    protected function getRemoteResourceLocalId(AbstractDataObject $resource, ResourceMapCollection $resourceMapCollection) : ?int
    {
        // find the database row that corresponds to the provided `$resource` object
        // this is checking if the remote ID already exists in the mapping table
        if ($localId = $this->getRemoteResourceLocalIdFromMappedIds($resource, $resourceMapCollection)) {
            return $localId;
        }

        // if there's no match from the mapping table, we can check for a matching unmapped resource
        if ($localId = $this->findUnmappedLocalResourceId($resource)) {
            // add the mapping to associate the resources
            $this->maybeMapToExistingLocalResource($resource, $localId);

            return $localId;
        }

        return null;
    }

    /**
     * Gets the local ID of the provided remote resource, searching outside the mapping table.
     * This can be used to find a local record that hasn't yet been officially mapped to a remote record via the
     * mapping table. For example: doing a manual SKU match for a product, or a manual slug match for a category.
     *
     * @param AbstractDataObject $resource
     * @return int|null
     */
    protected function findUnmappedLocalResourceId(AbstractDataObject $resource) : ?int
    {
        return null;
    }

    /**
     * Maps the remote resource to the local ID, if possible.
     *
     * @param AbstractDataObject $resource
     * @param int $localId
     * @return void
     */
    protected function maybeMapToExistingLocalResource(AbstractDataObject $resource, int $localId) : void
    {
        // to be implemented by child classes where required
        $remoteResourceId = $resource->{$this->remoteObjectIdProperty} ?? null;
        if ($remoteResourceId && is_string($remoteResourceId)) {
            try {
                $this->resourceMapRepository->add($localId, $remoteResourceId);
            } catch(WordPressDatabaseException $e) {
                SentryException::getNewInstance("Failed to associate remote {$this->resourceMapRepository->getResourceType()} {$remoteResourceId} with local object {$localId}.", $e);
            }
        }
    }

    /**
     * Finds the local ID that corresponds to the provided `$resource` object.
     * If we cannot find a matching result then `null` is returned.
     *
     * @param AbstractDataObject $resource
     * @param ResourceMapCollection $resourceMapCollection
     * @return int|null
     */
    protected function getRemoteResourceLocalIdFromMappedIds(AbstractDataObject $resource, ResourceMapCollection $resourceMapCollection) : ?int
    {
        return $resourceMapCollection->getLocalId($resource->{$this->remoteObjectIdProperty});
    }

    /**
     * Determines whether the provided remote resource should be inserted into the local database.
     * This method is called after we've already determined that there is no local record. This method may be
     * used to perform actions such as: check if the remote resource has been "soft deleted".
     *
     * @param AbstractDataObject $remoteResource
     * @return bool
     */
    protected function shouldInsertLocalResource(AbstractDataObject $remoteResource) : bool
    {
        return true;
    }

    /**
     * May broadcasts an event to indicate that new resources have been inserted into the local resource service.
     *
     * By default, this method does nothing. Child classes may override this method to broadcast the event.
     *
     * @param AbstractResourceAssociation[] $associations
     *
     * @codeCoverageIgnore there's nothing to be tested in this method
     *
     * @return void
     */
    protected function maybeBroadcastInsertedResourcesEvent(array $associations) : void
    {
    }

    /**
     * May insert the provided resource into the local resource service if it should be inserted.
     *
     * @param AbstractDataObject $resource
     * @return int|null
     */
    protected function maybeInsertLocalId(AbstractDataObject $resource) : ?int
    {
        if (! $this->shouldInsertLocalResource($resource)) {
            return null;
        }

        // otherwise we have to create a new local resource
        return $this->insertLocalResourceService->insert($resource);
    }
}
