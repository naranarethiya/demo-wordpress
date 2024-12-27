<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts;

/**
 * Describes objects that can build a payload using the information passed as input.
 */
interface PayloadBuilderContract
{
    /**
     * Builds payload based on the given payload input.
     *
     * @param PayloadInputContract $input
     * @return PayloadContract
     */
    public function build(PayloadInputContract $input) : PayloadContract;
}
