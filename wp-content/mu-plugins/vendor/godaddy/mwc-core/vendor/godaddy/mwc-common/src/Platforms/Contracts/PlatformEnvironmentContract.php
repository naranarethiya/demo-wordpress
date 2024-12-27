<?php

namespace GoDaddy\WordPress\MWC\Common\Platforms\Contracts;

/**
 * Platform environment contract.
 */
interface PlatformEnvironmentContract
{
    /**
     * Gets the platform environment name (e.g. staging, production).
     *
     * @return string
     */
    public function getEnvironment() : string;

    /**
     * Sets the environment name (e.g. staging, production).
     *
     * @param string $value
     *
     * @return $this
     */
    public function setEnvironment(string $value) : PlatformEnvironmentContract;

    /**
     * Checks if it's a production environment.
     *
     * @return bool
     */
    public function isProduction() : bool;

    /**
     * Checks if it's a staging environment.
     *
     * @return bool
     */
    public function isStaging() : bool;

    /**
     * Checks if it's a local development environment.
     *
     * @return bool
     */
    public function isLocal() : bool;

    /**
     * Checks if it's a test environment.
     *
     * @return bool
     */
    public function isTest() : bool;
}
