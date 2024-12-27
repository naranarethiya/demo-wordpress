<?php

namespace GoDaddy\WordPress\MWC\Core\Providers;

use GoDaddy\WordPress\MWC\Common\Container\Providers\AbstractServiceProvider;
use GoDaddy\WordPress\MWC\Common\Models\Contracts\HostingPlanContract;
use GoDaddy\WordPress\MWC\Common\Platforms\Contracts\PlatformRepositoryContract;

class HostingPlanServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [HostingPlanContract::class];

    /**
     * {@inheritDoc}
     */
    public function register() : void
    {
        $this->getContainer()->singleton(HostingPlanContract::class, function () {
            /** @var PlatformRepositoryContract $platformRepository */
            $platformRepository = $this->getContainer()->get(PlatformRepositoryContract::class);

            return $platformRepository->getPlan();
        });
    }
}
