<?php

namespace GoDaddy\WordPress\MWC\Common\Auth\Providers\EmailsService\Cache\Types;

use GoDaddy\WordPress\MWC\Common\Cache\Cache;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

/**
 * Cache for errors received trying to get a token for the Emails Service.
 */
class ErrorResponseCache extends Cache
{
    use CanGetNewInstanceTrait;

    /** @var string the type of object we are caching */
    protected $type = 'emails_service_token_error';

    /** @var int how long in seconds should the cache be kept for */
    protected $expires = 900;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->key($this->type);
    }
}
