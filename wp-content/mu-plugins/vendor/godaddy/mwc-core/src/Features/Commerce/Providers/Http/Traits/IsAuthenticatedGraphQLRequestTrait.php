<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Http\Traits;

use GoDaddy\WordPress\MWC\Common\Contracts\GraphQLOperationContract;

trait IsAuthenticatedGraphQLRequestTrait
{
    /**
     * Gets a new instance of the request after trying to set the authentication method.
     *
     * @param GraphQLOperationContract $operation
     * @return static
     */
    public static function withAuth(GraphQLOperationContract $operation)
    {
        return static::getNewInstance($operation)->tryToSetAuthMethod();
    }
}
