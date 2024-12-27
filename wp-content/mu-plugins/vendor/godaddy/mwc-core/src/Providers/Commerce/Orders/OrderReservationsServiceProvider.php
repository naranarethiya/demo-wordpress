<?php

namespace GoDaddy\WordPress\MWC\Core\Providers\Commerce\Orders;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\InventoryIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\OrderReservationsService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts\OrderReservationsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\NoopOrderReservationsService;

class OrderReservationsServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [OrderReservationsServiceContract::class];

    /**
     * {@inheritDoc}
     */
    public function register() : void
    {
        $concrete = InventoryIntegration::isEnabled() ? OrderReservationsService::class : NoopOrderReservationsService::class;

        $this->getContainer()->bind(OrderReservationsServiceContract::class, $concrete);
    }
}
