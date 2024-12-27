<?php

namespace GoDaddy\WordPress\MWC\Common\Platforms\Contracts;

/**
 * Platform environment builder contract.
 */
interface PlatformEnvironmentBuilderContract
{
    /**
     * Builds a PlatformEnvironment object.
     *
     * @return PlatformEnvironmentContract
     */
    public function build() : PlatformEnvironmentContract;

    /**
     * Gets the environment name.
     *
     * @return string
     */
    public function getEnvironmentName() : string;
}
