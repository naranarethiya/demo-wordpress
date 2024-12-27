<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services;

use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Contracts\CanPersistMultiItemsRemoteIdsContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\MappingServiceContract;

/**
 * @template TLocalItem of object
 * @implements CanPersistMultiItemsRemoteIdsContract<TLocalItem>
 */
abstract class AbstractMultiItemsPersistentMappingService implements CanPersistMultiItemsRemoteIdsContract
{
    protected MappingServiceContract $mappingService;

    public function __construct(MappingServiceContract $mappingService)
    {
        $this->mappingService = $mappingService;
    }

    /**
     * {@inheritDoc}
     */
    public function persistRemoteIds(array $items) : void
    {
        foreach ($items as $item) {
            $this->persistRemoteId($item);
        }
    }

    /**
     * Persist remote ID for the given local item.
     *
     * @param TLocalItem $item
     */
    protected function persistRemoteId(object $item) : void
    {
        if (! $remoteId = $this->mappingService->getRemoteId($item)) {
            return;
        }

        try {
            $this->mappingService->saveRemoteId($item, $remoteId);
        } catch (CommerceExceptionContract $exception) {
            SentryException::getNewInstance($exception->getMessage(), $exception);
        }
    }
}
