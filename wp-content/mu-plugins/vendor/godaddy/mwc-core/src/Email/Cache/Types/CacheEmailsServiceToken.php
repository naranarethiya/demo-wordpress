<?php

namespace GoDaddy\WordPress\MWC\Core\Email\Cache\Types;

use GoDaddy\WordPress\MWC\Common\Cache\Cache;
use GoDaddy\WordPress\MWC\Common\Helpers\DeprecationHelper;
use GoDaddy\WordPress\MWC\Common\Traits\IsSingletonTrait;

/**
 * Emails service token cache handler class.
 *
 * @deprecated
 */
final class CacheEmailsServiceToken extends Cache
{
    use IsSingletonTrait;
    /** @var int how long in seconds should the cache be kept for */
    protected $expires = 7200;

    /** @var string the cache key */
    protected $key = 'email_service_token';

    /**
     * Constructor.
     */
    public function __construct()
    {
        DeprecationHelper::deprecatedFunction(__CLASS__, '3.2.2');

        $this->type('email_service_token');
    }
}
