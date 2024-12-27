<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services;

use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts\LineItemMappingStrategyContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Hash\LineItemHashService;

/**
 * @extends AbstractItemTemporaryMappingStrategy<LineItem>
 */
class TemporaryLineItemMappingStrategy extends AbstractItemTemporaryMappingStrategy implements LineItemMappingStrategyContract
{
    protected LineItemHashService $lineItemHashService;

    /**
     * Constructor.
     *
     * @param LineItemHashService $lineItemHashService
     */
    public function __construct(LineItemHashService $lineItemHashService)
    {
        $this->lineItemHashService = $lineItemHashService;
    }

    /**
     * {@inheritDoc}
     */
    protected function getModelHash(object $model) : string
    {
        return $this->lineItemHashService->getModelHash($model);
    }
}
