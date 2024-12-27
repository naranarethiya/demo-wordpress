<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts;

use GoDaddy\WordPress\MWC\Common\Contracts\CanConvertToArrayContract;

/**
 * Describes input objects used to build payloads from the data objects that we use to represent remote resources.
 *
 * @template TResource of CanConvertToArrayContract
 * @template TModel of object
 */
interface UpdateResourcePayloadInputContract extends PayloadInputContract
{
    /**
     * Gets original DTO resource.
     *
     * @return TResource
     */
    public function getOriginalResource() : CanConvertToArrayContract;

    /**
     * Gets modified DTO resource.
     *
     * @return TResource
     */
    public function getModifiedResource() : CanConvertToArrayContract;

    /**
     * Gets native object model.
     *
     * @return TModel
     */
    public function getModel() : object;
}
