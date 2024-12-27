<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services;

use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Hash\LineItemHashService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\LineItemMapRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\AbstractMappingStrategyFactory;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\MappingStrategyContract;

class LineItemMappingStrategyFactory extends AbstractMappingStrategyFactory
{
    protected LineItemMapRepository $lineItemMapRepository;

    protected LineItemHashService $lineItemHashService;

    public function __construct(CommerceContextContract $commerceContext, LineItemMapRepository $lineItemMapRepository, LineItemHashService $lineItemHashService)
    {
        $this->lineItemMapRepository = $lineItemMapRepository;
        $this->lineItemHashService = $lineItemHashService;

        parent::__construct($commerceContext);
    }

    /**
     * @param LineItem $model
     * {@inheritDoc}
     */
    public function getPrimaryMappingStrategyFor(object $model) : ?MappingStrategyContract
    {
        if (! $model->getId()) {
            return null;
        }

        return new LineItemMappingStrategy($this->lineItemMapRepository);
    }

    /**
     * {@inheritDoc}
     */
    public function getSecondaryMappingStrategy() : MappingStrategyContract
    {
        return new TemporaryLineItemMappingStrategy($this->lineItemHashService);
    }
}
