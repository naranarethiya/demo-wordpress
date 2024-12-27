<?php

namespace GoDaddy\WordPress\MWC\Core\Providers\Commerce\Orders;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts\MultiNotesMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts\MultiNotesPersistentMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts\NoteMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\MultiNotesMappingService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\MultiNotesPersistentMappingService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\NoteMappingService;

class NoteMappingServiceProvider extends AbstractServiceProvider
{
    /** @var string[] */
    protected array $provides = [
        NoteMappingServiceContract::class,
        MultiNotesMappingServiceContract::class,
        MultiNotesPersistentMappingServiceContract::class,
    ];

    /**
     * {@inheritDoc}
     */
    public function register() : void
    {
        $this->getContainer()->bind(NoteMappingServiceContract::class, NoteMappingService::class);
        $this->getContainer()->bind(MultiNotesMappingServiceContract::class, MultiNotesMappingService::class);
        $this->getContainer()->bind(MultiNotesPersistentMappingServiceContract::class, MultiNotesPersistentMappingService::class);
    }
}
