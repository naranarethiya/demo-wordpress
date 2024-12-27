<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts;

use GoDaddy\WordPress\MWC\Common\Contracts\CanConvertToArrayContract;

/**
 * Describes a payload builder that uses the field name associated with the payload that it’s trying to build.
 *
 * @template TResource of CanConvertToArrayContract
 * @template TModel of object
 */
interface HasPayloadFieldNameContract
{
    /**
     * Gets field name.
     *
     * @return string
     */
    public function getFieldName() : ?string;

    /**
     * Sets field name.
     *
     * @param string $value
     * @return $this
     */
    public function setFieldName(string $value) : HasPayloadFieldNameContract;
}
