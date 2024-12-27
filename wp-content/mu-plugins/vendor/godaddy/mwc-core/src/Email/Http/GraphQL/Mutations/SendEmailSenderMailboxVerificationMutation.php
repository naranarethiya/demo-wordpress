<?php

namespace GoDaddy\WordPress\MWC\Core\Email\Http\GraphQL\Mutations;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

class SendEmailSenderMailboxVerificationMutation extends AbstractGraphQLOperation
{
    use CanGetNewInstanceTrait;
    /** {@inheritdoc} */
    protected $operation = 'mutation sendEmailSenderMailboxVerification($emailAddress: String!, $siteId: ID!, $mailboxVerificationRedirectUrl: String!) {
  sendEmailSenderMailboxVerification(input: {
    emailAddress: $emailAddress,
    siteId: $siteId,
    mailboxVerificationRedirectUrl: $mailboxVerificationRedirectUrl
  })
}';

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->setAsMutation();
    }
}
