<?php

namespace GoDaddy\WordPress\MWC\Core\Platforms\Builders;

use GoDaddy\WordPress\MWC\Common\Platforms\Contracts\PlatformEnvironmentBuilderContract;
use GoDaddy\WordPress\MWC\Common\Platforms\Contracts\PlatformEnvironmentContract;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformEnvironment;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

/**
 * Managed WordPress platform environment builder class.
 */
class PlatformEnvironmentBuilder implements PlatformEnvironmentBuilderContract
{
    use CanGetNewInstanceTrait;

    /**
     * {@inheritDoc}
     */
    public function build() : PlatformEnvironmentContract
    {
        return (new PlatformEnvironment())->setEnvironment($this->getEnvironmentName());
    }

    /** {@inheritDoc} */
    public function getEnvironmentName() : string
    {
        // Currently forcing a 'production' environment always.
        return PlatformEnvironment::PRODUCTION;
    }
}
