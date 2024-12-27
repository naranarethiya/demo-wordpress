<?php

namespace GoDaddy\WordPress\MWC\Common\Stores\Contracts;

/**
 * A contract for store repositories.
 */
interface StoreRepositoryContract
{
    /**
     * Gets the store ID.
     *
     * @return string|null
     */
    public function getStoreId() : ?string;

    /**
     * Determines the default store ID.
     *
     * @return string|null
     */
    public function determineDefaultStoreId() : ?string;

    /**
     * Sets the default store ID.
     *
     * @param string $storeId
     * @return void
     */
    public function setDefaultStoreId(string $storeId) : void;

    /**
     * Registers a store to a customer channel.
     *
     * @param string $storeId
     * @param string $businessId
     * @return void
     */
    public function registerStore(string $storeId, string $businessId) : void;

    /**
     * Lists stores for a customer channel.
     *
     * @param array<string, mixed>|null $args
     * @return array<string, mixed>
     */
    public function listStores(?array $args = []) : array;
}
