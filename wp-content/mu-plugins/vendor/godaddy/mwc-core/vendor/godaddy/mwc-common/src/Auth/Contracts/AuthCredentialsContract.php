<?php

namespace GoDaddy\WordPress\MWC\Common\Auth\Contracts;

use GoDaddy\WordPress\MWC\Common\Contracts\CanConvertToArrayContract;
use GoDaddy\WordPress\MWC\Common\Contracts\CanSeedContract;

/**
 * Contract for seeds authentication credentials and returns as array.
 */
interface AuthCredentialsContract extends CanConvertToArrayContract, CanSeedContract
{
}
