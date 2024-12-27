<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services;

use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Contracts\CanGetModelHashContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Contracts\CanSaveMultiItemsRemoteIdsContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\MappingServiceContract;

/**
 * @template TLocalItem of object
 * @template TCommerceItem of object{id: ?string}
 * @implements CanSaveMultiItemsRemoteIdsContract<TLocalItem, TCommerceItem>
 */
class AbstractMultiItemsMappingService implements CanSaveMultiItemsRemoteIdsContract
{
    protected MappingServiceContract $mappingService;

    protected CanGetModelHashContract $localModelHashService;

    protected CanGetModelHashContract $commerceObjectHashService;

    public function __construct(
        MappingServiceContract $mappingService,
        CanGetModelHashContract $localModelHashService,
        CanGetModelHashContract $commerceObjectHashService
    ) {
        $this->mappingService = $mappingService;
        $this->localModelHashService = $localModelHashService;
        $this->commerceObjectHashService = $commerceObjectHashService;
    }

    /**
     * @param TLocalItem[] $items
     * @param TCommerceItem[] $commerceItems
     *
     * {@inheritDoc}
     */
    public function saveRemoteIds(array $items, array $commerceItems) : void
    {
        $commerceLineItemsIndexed = $this->getCommerceItemsIndexed($commerceItems);
        $localLineItemsIndexed = $this->getLocalItemsIndexed($items);

        foreach ($localLineItemsIndexed as $key => $lineItem) {
            $this->saveRemoteIdInIndex($key, $lineItem, $commerceLineItemsIndexed);
        }
    }

    /**
     * Saves a single remote ID.
     *
     * @param int|string      $key
     * @param TLocalItem      $localItem
     * @param TCommerceItem[] $commerceItemsIndexed
     *
     * @return void
     */
    protected function saveRemoteIdInIndex($key, object $localItem, array $commerceItemsIndexed) : void
    {
        if (isset($commerceItemsIndexed[$key])) {
            $this->saveItemRemoteId($localItem, $commerceItemsIndexed[$key]);
        } else {
            // API response doesn't contain a matching item
            SentryException::getNewInstance("Could not find a matching commerce line item at index {$key}");
        }
    }

    /**
     * Save a single item remote ID using the mapping service.
     *
     * @param TLocalItem $localItem
     * @param TCommerceItem $commerceItem
     *
     * @return void
     */
    protected function saveItemRemoteId(object $localItem, object $commerceItem) : void
    {
        try {
            $this->mappingService->saveRemoteId($localItem, TypeHelper::string($commerceItem->id, ''));
        } catch (CommerceExceptionContract $exception) {
            SentryException::getNewInstance("Could not save remote ID of commerce line item {$commerceItem->id}", $exception);
        }
    }

    /**
     * Gets local items indexed for easy match to commerce items.
     *
     * Concrete implementations may override to index by other value, e.g., positional index.
     *
     * @param TLocalItem[] $localItems
     *
     * @return array<int|string, TLocalItem>
     */
    protected function getLocalItemsIndexed(array $localItems) : array
    {
        return $this->getItemsIndexedByHash($localItems, $this->localModelHashService);
    }

    /**
     * Gets commerce items indexed for easy match to local items.
     *
     * Concrete implementations may override to index by other value, e.g., positional index.
     *
     * @param TCommerceItem[] $commerceItems
     *
     * @return array<int|string, TCommerceItem>
     */
    protected function getCommerceItemsIndexed(array $commerceItems) : array
    {
        return $this->getItemsIndexedByHash($commerceItems, $this->commerceObjectHashService);
    }

    /**
     * @template TItem of TLocalItem|TCommerceItem
     *
     * @param TItem[] $items
     * @param CanGetModelHashContract $hashService
     *
     * @return array<string, TItem>
     */
    protected function getItemsIndexedByHash(array $items, CanGetModelHashContract $hashService) : array
    {
        $itemsIndexedByHash = [];

        foreach ($items as $item) {
            $itemsIndexedByHash[$hashService->getModelHash($item)] = $item;
        }

        return $itemsIndexedByHash;
    }
}
