<?php

namespace GoDaddy\WordPress\MWC\Common\Http\Contracts;

/**
 * A contract for all requests that interact with the MWC API.
 */
interface GoDaddyRequestContract extends RequestContract
{
    /**
     * Gets a new instance of the request after trying to set the authentication method.
     *
     * @return static
     */
    public static function withAuth();

    /**
     * Constructor.
     *
     * The contract requires that the constructor has no required parameters to
     * allow new static() to be used safely.
     */
    public function __construct();
}
