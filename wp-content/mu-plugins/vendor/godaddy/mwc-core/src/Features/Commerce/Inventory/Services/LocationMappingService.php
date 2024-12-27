<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services;

use GoDaddy\WordPress\MWC\Common\Exceptions\WordPressDatabaseException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\CommerceException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\LocationMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\LocationMapRepository;

class LocationMappingService implements LocationMappingServiceContract
{
    protected LocationMapRepository $locationMapRepository;
    protected CommerceContextContract $commerceContext;

    /**
     * @param LocationMapRepository $locationMapRepository
     * @param CommerceContextContract $commerceContext
     */
    public function __construct(
        LocationMapRepository $locationMapRepository,
        CommerceContextContract $commerceContext
    ) {
        $this->locationMapRepository = $locationMapRepository;
        $this->commerceContext = $commerceContext;
    }

    /**
     * {@inheritDoc}
     *
     * @throws CommerceException
     */
    public function saveRemoteId(string $remoteId) : void
    {
        if (! $localId = $this->commerceContext->getId()) {
            throw new CommerceException('The local ID cannot be zero.');
        }

        try {
            $this->locationMapRepository->add($localId, $remoteId);
        } catch (WordPressDatabaseException $exception) {
            throw new CommerceException("A database error occurred trying to save the remote location UUID: {$exception->getMessage()}");
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getRemoteId() : ?string
    {
        return $this->locationMapRepository->getRemoteId($this->commerceContext->getId() ?? 0);
    }
}
