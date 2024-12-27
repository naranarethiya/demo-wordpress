<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts;

interface MappingStrategyFactoryContract
{
    /**
     * Gets a mapping strategy for the given model. This method should normally return the primary strategy for a
     * resource or the secondary strategy if no other strategy is available.
     *
     * @param object $model
     *
     * @return MappingStrategyContract
     */
    public function getStrategyFor(object $model) : MappingStrategyContract;

    /**
     * Gets the primary mapping strategies for the given model. Primary strategies usually deal with records that
     * already have a local ID.
     *
     * @param object $model
     *
     * @return MappingStrategyContract|null
     */
    public function getPrimaryMappingStrategyFor(object $model) : ?MappingStrategyContract;

    /**
     * Gets the secondary strategy for the resource. A secondary strategy is responsible for saving and retrieving
     * remote IDs from an in-memory storage. They deal with models that have no local ID, yet.
     *
     * @return MappingStrategyContract
     */
    public function getSecondaryMappingStrategy() : MappingStrategyContract;
}
