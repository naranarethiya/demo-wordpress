<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services;

use Exception;
use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\CommerceException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\ReservationMappingStrategyContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\ReservationMapRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\AbstractMappingStrategyFactory;

class ReservationMappingStrategyFactory extends AbstractMappingStrategyFactory
{
    protected ReservationMapRepository $reservationMapRepository;

    public function __construct(CommerceContextContract $commerceContext, ReservationMapRepository $reservationMapRepository)
    {
        parent::__construct($commerceContext);

        $this->reservationMapRepository = $reservationMapRepository;
    }

    /**
     * {@inheritDoc}
     */
    public function getPrimaryMappingStrategyFor(object $model) : ?ReservationMappingStrategyContract
    {
        if ($model instanceof LineItem && $model->getId()) {
            return ReservationMappingStrategy::getNewInstance($this->reservationMapRepository);
        }

        return null;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function getSecondaryMappingStrategy() : ReservationMappingStrategyContract
    {
        throw new CommerceException('Secondary mapping strategy is unavailable');
    }
}
