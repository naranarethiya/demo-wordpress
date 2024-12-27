<?php

namespace GoDaddy\WordPress\MWC\Common\Contracts;

/**
 * Can Seed Contract.
 */
interface CanSeedContract
{
    /**
     * Seeds an instance of the given class without saving it.
     *
     * Classes implementing this contract can update this method to expect an array of property values and set the model properties.
     *
     * @return static
     */
    public static function seed();
}
