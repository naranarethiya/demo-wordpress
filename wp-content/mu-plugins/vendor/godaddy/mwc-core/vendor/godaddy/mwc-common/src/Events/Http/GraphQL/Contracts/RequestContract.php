<?php

namespace GoDaddy\WordPress\MWC\Common\Events\Http\GraphQL\Contracts;

use GoDaddy\WordPress\MWC\Common\Contracts\GraphQLOperationContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\RequestContract as HttpRequestContract;

/**
 * A contract for all requests that interact with the Events API.
 */
interface RequestContract extends HttpRequestContract
{
    /**
     * Gets a new instance of the request after trying to set the authentication method.
     *
     * @return static
     */
    public static function withAuth(GraphQLOperationContract $operation);

    /**
     * Constructor.
     *
     * The contract requires that the constructor has GraphQL operation to allow
     * new static() to be used safely.
     */
    public function __construct(GraphQLOperationContract $operation);
}
