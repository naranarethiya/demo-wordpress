<?php

namespace GoDaddy\WordPress\MWC\Core\Email\Http\GraphQL\Mutations;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;

/**
 * Mutation for issuing a JWT for a site.
 */
class IssueTokenForSiteJwtMutation extends AbstractGraphQLOperation
{
    /** @var string */
    protected $operation = 'mutation issueTokenForSiteJwt($accessToken: ID!, $siteIdentifier: ID!) {issueTokenForSiteJwt(accessToken: $accessToken, siteIdentifier: $siteIdentifier)}';

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->setAsMutation();
    }
}
