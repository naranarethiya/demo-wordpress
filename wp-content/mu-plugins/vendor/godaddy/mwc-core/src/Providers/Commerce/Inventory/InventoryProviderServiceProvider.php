<?php

namespace GoDaddy\WordPress\MWC\Core\Providers\Commerce\Inventory;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts\InventoryProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\InventoryProvider;

class InventoryProviderServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [InventoryProviderContract::class];

    /**
     * {@inheritDoc}
     */
    public function register() : void
    {
        $this->getContainer()->singleton(InventoryProviderContract::class, InventoryProvider::class);
    }
}
