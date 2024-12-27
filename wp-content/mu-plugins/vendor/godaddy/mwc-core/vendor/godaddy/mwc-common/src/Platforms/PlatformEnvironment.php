<?php

namespace GoDaddy\WordPress\MWC\Common\Platforms;

use GoDaddy\WordPress\MWC\Common\Platforms\Contracts\PlatformEnvironmentContract;

/**
 * Platform environment object.
 */
class PlatformEnvironment implements PlatformEnvironmentContract
{
    const PRODUCTION = 'production';
    const STAGING = 'staging';
    const LOCAL = 'development';
    const TEST = 'testing';

    /** @var string */
    protected $environment;

    /** {@inheritDoc} */
    public function getEnvironment() : string
    {
        return $this->environment;
    }

    /** {@inheritDoc} */
    public function setEnvironment(string $value) : PlatformEnvironment
    {
        $this->environment = $value;

        return $this;
    }

    /** {@inheritDoc} */
    public function isProduction() : bool
    {
        return static::PRODUCTION === $this->getEnvironment();
    }

    /** {@inheritDoc} */
    public function isStaging() : bool
    {
        return static::STAGING === $this->getEnvironment();
    }

    /** {@inheritDoc} */
    public function isLocal() : bool
    {
        return static::LOCAL === $this->getEnvironment();
    }

    /** {@inheritDoc} */
    public function isTest() : bool
    {
        return static::TEST === $this->getEnvironment();
    }
}
