<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Models;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Repositories\AbstractResourceMapRepository;

/**
 * @phpstan-import-type TResourceMapRow from AbstractResourceMapRepository
 */
class ResourceMapCollection
{
    /** @var array<int, ResourceMap>|null */
    protected ?array $indexedByLocalId = null;

    /** @var array<string, ResourceMap>|null */
    protected ?array $indexedByRemoteId = null;

    /** @var ResourceMap[] */
    protected array $resourceMaps;

    /**
     * Constructor.
     *
     * @param ResourceMap[] $resourceMaps
     */
    final public function __construct(array $resourceMaps)
    {
        $this->resourceMaps = $resourceMaps;
    }

    /**
     * @param TResourceMapRow[] $rows
     * @return ResourceMapCollection
     */
    public static function fromRows(array $rows) : ResourceMapCollection
    {
        return new static(array_map([ResourceMap::class, 'fromRow'], $rows));
    }

    /**
     * Gets the array of resource maps.
     *
     * @return ResourceMap[]
     */
    public function getResourceMaps() : array
    {
        return $this->resourceMaps;
    }

    /**
     * Looks up a remote ID by local ID from the collection.
     *
     * @param string $remoteId
     * @return int|null
     */
    public function getLocalId(string $remoteId) : ?int
    {
        return $this->getIndexedByRemoteId()[$remoteId]->localId ?? null;
    }

    /**
     * Returns a positional array of all local IDs in the collection.
     *
     * @return int[]
     */
    public function getLocalIds() : array
    {
        return array_keys($this->getIndexedByLocalId());
    }

    /**
     * Looks up a remote ID by local ID from the collection.
     *
     * @param int $localId
     * @return string|null
     */
    public function getRemoteId(int $localId) : ?string
    {
        return $this->getIndexedByLocalId()[$localId]->commerceId ?? null;
    }

    /**
     * Returns a positional array of all remote IDs in the collection.
     *
     * @return string[]
     */
    public function getRemoteIds() : array
    {
        return array_keys($this->getIndexedByRemoteId());
    }

    /**
     * Get memoized array of ResourceMap indexed by local ID.
     *
     * @return array<int, ResourceMap>
     */
    protected function getIndexedByLocalId() : array
    {
        return $this->indexedByLocalId ??= ArrayHelper::indexBy(
            $this->resourceMaps,
            static fn (ResourceMap $resourceMap) : int => $resourceMap->localId
        );
    }

    /**
     * Get memoized array of ResourceMap indexed by remote ID.
     *
     * @return array<string, ResourceMap>
     */
    protected function getIndexedByRemoteId() : array
    {
        return $this->indexedByRemoteId ??= ArrayHelper::indexBy(
            $this->resourceMaps,
            static fn (ResourceMap $resourceMap) : string => $resourceMap->commerceId
        );
    }
}
