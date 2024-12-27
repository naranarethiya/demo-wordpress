<?php

namespace GoDaddy\WordPress\MWC\Core\Email\Http\GraphQL\Mutations;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;

class IssueTokenForSiteMutation extends AbstractGraphQLOperation
{
    /** {@inheritdoc} */
    protected $operation = 'mutation issueSiteToken($siteId: ID!, $uid: ID!, $siteToken: ID!, $platform: Platform = MWP) {issueTokenForSite(siteId: $siteId, uid: $uid, siteToken: $siteToken, platform: $platform)}';

    /**
     * IssueTokenForSiteMutation constructor.
     */
    public function __construct()
    {
        $this->setAsMutation();
    }
}
