<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts;

/**
 * Describes objects that can be used as input for payload builders.
 *
 * Since payload builders could be used to build payloads for different types of requests, this interface is meant to
 * group all the possible inputs that those builders would need without enforcing any methods. Each family of payload
 * builders should extend this contract to enforce the required input.
 */
interface PayloadInputContract
{
}
