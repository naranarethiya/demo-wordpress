<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts\OrdersMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\AbstractMappingService;

class OrdersMappingService extends AbstractMappingService implements OrdersMappingServiceContract
{
    /**
     * {@inheritDoc}
     *
     * @param OrderMappingStrategyFactory $orderMappingStrategyFactory
     */
    public function __construct(OrderMappingStrategyFactory $orderMappingStrategyFactory)
    {
        parent::__construct($orderMappingStrategyFactory);
    }
}
