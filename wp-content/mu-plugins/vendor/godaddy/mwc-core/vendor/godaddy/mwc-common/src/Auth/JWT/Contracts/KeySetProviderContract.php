<?php

namespace GoDaddy\WordPress\MWC\Common\Auth\JWT\Contracts;

/**
 * Contract to provide JSON web key set (JWKS).
 */
interface KeySetProviderContract
{
    /**
     * Get a JSON web key set (JWKS) in array form.
     *
     * @return mixed[]
     */
    public function getKeySet() : array;
}
