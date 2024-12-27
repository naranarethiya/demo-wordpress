<?php

namespace GoDaddy\WordPress\MWC\Common\Models\Contracts;

use GoDaddy\WordPress\MWC\Common\Contracts\CanConvertToArrayContract;
use GoDaddy\WordPress\MWC\Common\Contracts\CanSeedContract;

/**
 * Model contract.
 */
interface ModelContract extends CanConvertToArrayContract, CanSeedContract
{
    /**
     * Creates a new instance of the given model class and saves it.
     *
     * Classes implementing this contract can update this method to expect an array of property values and set the model properties.
     *
     * @return $this|null|mixed
     */
    public static function create();

    /**
     * Gets an instance of the given model class, if found.
     *
     * @param mixed $identifier
     * @return ModelContract|mixed
     */
    public static function get($identifier);

    /**
     * Updates a given instance of the model class and saves it.
     *
     * Classes implementing this contract can update this method to expect an array of property values and set the model properties.
     *
     * @return $this|mixed
     */
    public function update();

    /**
     * Deletes a given instance of the model class.
     *
     * @return $this|mixed
     */
    public function delete();

    /**
     * Saves the instance of the class with its current state.
     *
     * @return $this|mixed
     */
    public function save();
}
