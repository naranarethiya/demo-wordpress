<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\LevelMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\AbstractMappingService;

class LevelMappingService extends AbstractMappingService implements LevelMappingServiceContract
{
    /**
     * The Level Mapping Service constructor.
     */
    public function __construct(LevelMappingStrategyFactory $levelMappingStrategyFactory)
    {
        parent::__construct($levelMappingStrategyFactory);
    }

    /**
     * {@inheritDoc}
     *
     * This is currently a no-op as there is no secondary strategy for levels.
     */
    protected function getRemoteIdUsingSecondaryStrategy(object $model) : ?string
    {
        return null;
    }
}
