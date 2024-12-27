<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\MappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\MappingStrategyFactoryContract;

/**
 * Abstract mapping service class.
 */
class AbstractMappingService implements MappingServiceContract
{
    /** @var MappingStrategyFactoryContract */
    protected MappingStrategyFactoryContract $mappingStrategyFactory;

    /**
     * Constructor.
     *
     * @param MappingStrategyFactoryContract $mappingStrategyFactory
     */
    public function __construct(MappingStrategyFactoryContract $mappingStrategyFactory)
    {
        $this->mappingStrategyFactory = $mappingStrategyFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function getRemoteId(object $model) : ?string
    {
        return $this->getRemoteIdUsingPrimaryStrategy($model) ?? $this->getRemoteIdUsingSecondaryStrategy($model);
    }

    /**
     * {@inheritDoc}
     */
    public function saveRemoteId(object $model, string $remoteId) : void
    {
        $this->mappingStrategyFactory->getStrategyFor($model)->saveRemoteId($model, $remoteId);
    }

    /**
     * Gets the remote ID of the given model using the primary mapping strategy.
     *
     * @param object $model
     * @return string|null
     */
    protected function getRemoteIdUsingPrimaryStrategy(object $model) : ?string
    {
        $strategy = $this->mappingStrategyFactory->getPrimaryMappingStrategyFor($model);

        return $strategy ? $strategy->getRemoteId($model) : null;
    }

    /**
     * Gets the remote ID of the given model using the secondary mapping strategy.
     *
     * @param object $model
     * @return string|null
     */
    protected function getRemoteIdUsingSecondaryStrategy(object $model) : ?string
    {
        return $this->mappingStrategyFactory->getSecondaryMappingStrategy()->getRemoteId($model);
    }
}
