<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\MappingStrategyContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\MappingStrategyFactoryContract;

abstract class AbstractMappingStrategyFactory implements MappingStrategyFactoryContract
{
    /** @var CommerceContextContract */
    protected CommerceContextContract $commerceContext;

    /**
     * Constructor.
     *
     * @param CommerceContextContract $commerceContext
     */
    public function __construct(CommerceContextContract $commerceContext)
    {
        $this->commerceContext = $commerceContext;
    }

    /**
     * {@inheritDoc}
     */
    public function getStrategyFor(object $model) : MappingStrategyContract
    {
        return $this->getPrimaryMappingStrategyFor($model) ?? $this->getSecondaryMappingStrategy();
    }
}
