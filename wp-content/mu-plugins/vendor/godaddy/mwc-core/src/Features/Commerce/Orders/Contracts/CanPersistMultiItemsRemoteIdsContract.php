<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Contracts;

/**
 * @template TLocalItem of object
 */
interface CanPersistMultiItemsRemoteIdsContract
{
    /**
     * Given local items, get their remote IDs, then save those remote IDs to persisted mapping storage.
     *
     * @param TLocalItem[] $items
     *
     * @return void
     */
    public function persistRemoteIds(array $items) : void;
}
