<?php

namespace GoDaddy\WordPress\MWC\Core\Providers\Commerce;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Contracts\CanGenerateIdContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\IdProvider;

class IdProviderServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [CanGenerateIdContract::class];

    /**
     * {@inheritDoc}
     */
    public function register() : void
    {
        $this->getContainer()->bind(CanGenerateIdContract::class, IdProvider::class);
    }
}
