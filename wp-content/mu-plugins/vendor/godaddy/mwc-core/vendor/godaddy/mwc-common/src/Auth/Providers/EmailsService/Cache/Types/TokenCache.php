<?php

namespace GoDaddy\WordPress\MWC\Common\Auth\Providers\EmailsService\Cache\Types;

use GoDaddy\WordPress\MWC\Common\Cache\Cache;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

/**
 * Emails service token cache handler class.
 */
class TokenCache extends Cache
{
    use CanGetNewInstanceTrait;
    /** @var int how long in seconds should the cache be kept for */
    protected $expires = 7200;

    /** @var string the cache key */
    protected $key = 'emails_service_token';

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->type('emails_service_token');
    }
}
