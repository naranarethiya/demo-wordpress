<?php

namespace GoDaddy\WordPress\MWC\Core\Providers\Auth\Sso\WordPress;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Core\Auth\Sso\WordPress\Services\CareUserService;
use GoDaddy\WordPress\MWC\Core\Auth\Sso\WordPress\Services\Contracts\CareUserServiceContract;

/**
 * Service provider for the {@see CareUserService} class.
 */
class CareUserServiceServiceProvider extends AbstractServiceProvider
{
    /** @var class-string[] */
    protected array $provides = [CareUserServiceContract::class];

    /**
     * {@inheritDoc}
     */
    public function register() : void
    {
        $this->getContainer()->bind(CareUserServiceContract::class, CareUserService::class);
    }
}
