<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services;

use GoDaddy\WordPress\MWC\Common\Models\Orders\Order;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Repositories\OrderMapRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts\OrderMappingStrategyContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\AbstractMappingStrategyFactory;

class OrderMappingStrategyFactory extends AbstractMappingStrategyFactory
{
    protected OrderMapRepository $resourceMapRepository;

    public function __construct(
        CommerceContextContract $commerceContext,
        OrderMapRepository $resourceMapRepository
    ) {
        parent::__construct($commerceContext);
        $this->resourceMapRepository = $resourceMapRepository;
    }

    /**
     * {@inheritDoc}
     */
    public function getPrimaryMappingStrategyFor(object $model) : ?OrderMappingStrategyContract
    {
        if ($model instanceof Order && $model->getId()) {
            return $this->getOrderMappingStrategy();
        }

        return null;
    }

    /**
     * Gets the mapping strategy for orders.
     *
     * @return OrderMappingStrategyContract
     */
    protected function getOrderMappingStrategy() : OrderMappingStrategyContract
    {
        return new OrderMappingStrategy($this->resourceMapRepository);
    }

    /**
     * {@inheritDoc}
     */
    public function getSecondaryMappingStrategy() : OrderMappingStrategyContract
    {
        return new TemporaryOrderMappingStrategy();
    }
}
