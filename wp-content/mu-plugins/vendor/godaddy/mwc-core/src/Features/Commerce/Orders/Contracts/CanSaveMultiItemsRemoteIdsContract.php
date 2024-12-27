<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Contracts;

/**
 * @template TLocalItem of object
 * @template TCommerceItem of object{id: ?string}
 */
interface CanSaveMultiItemsRemoteIdsContract
{
    /**
     * Saves all the given line items' remote IDs by matching line item models to commerce line item data objects.
     * @param TLocalItem[] $items
     * @param TCommerceItem[] $commerceItems
     *
     * @return void
     */
    public function saveRemoteIds(array $items, array $commerceItems) : void;
}
