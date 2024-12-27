<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Services;

use GoDaddy\WordPress\MWC\Common\Exceptions\WordPressDatabaseException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\CommerceException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\MappingStrategyContract;
use GoDaddy\WordPress\MWC\Core\Repositories\AbstractResourceMapRepository;

abstract class AbstractMappingStrategy implements MappingStrategyContract
{
    protected AbstractResourceMapRepository $resourceMapRepository;

    public function __construct(AbstractResourceMapRepository $resourceMapRepository)
    {
        $this->resourceMapRepository = $resourceMapRepository;
    }

    /**
     * Save mapping of local to remote ID to the database.
     *
     * @param int $localId
     * @param string $remoteId
     * @throws CommerceException
     */
    protected function saveMapping(int $localId, string $remoteId) : void
    {
        if (! $localId) {
            throw new CommerceException('The local ID cannot be zero.');
        }

        if (empty($remoteId)) {
            throw new CommerceException('The remote ID cannot be empty.');
        }

        try {
            $this->saveMappingUsingRepository($localId, $remoteId);
        } catch (WordPressDatabaseException $exception) {
            throw new CommerceException("A database error occurred trying to save the remote UUID: {$exception->getMessage()}");
        }
    }

    /**
     * Saves a mapping of the given local ID to the given remote ID using the configured map repository.
     *
     * @param int $localId
     * @param non-empty-string $remoteId
     * @throws WordPressDatabaseException
     */
    protected function saveMappingUsingRepository(int $localId, string $remoteId) : void
    {
        $this->resourceMapRepository->add($localId, $remoteId);
    }
}
