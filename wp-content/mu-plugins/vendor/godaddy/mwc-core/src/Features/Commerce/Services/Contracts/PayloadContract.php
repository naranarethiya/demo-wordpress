<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts;

/**
 * Describes objects that hold payload data.
 *
 * @phpstan-type TPayloadValue scalar|mixed[]|null
 */
interface PayloadContract
{
    /**
     * Gets whether payload has value or not.
     *
     * @return bool
     */
    public function hasValue() : bool;

    /**
     * Gets payload value.
     *
     * @return TPayloadValue
     */
    public function getValue();
}
