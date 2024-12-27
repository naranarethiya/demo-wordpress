<?php

namespace GoDaddy\WordPress\MWC\Common\Cache\Types;

use Exception;
use GoDaddy\WordPress\MWC\Common\Cache\Cache;
use GoDaddy\WordPress\MWC\Common\Cache\Contracts\CacheableContract;
use GoDaddy\WordPress\MWC\Common\Http\Request;

/**
 * HTTP Response cache.
 */
final class CacheHttpResponse extends Cache implements CacheableContract
{
    /** @var int how long in seconds do we keep this cache */
    protected $expires = 86000;

    /** @var string the key prefix */
    protected $keyPrefix = 'gd_http_response_';

    /**
     * Sets a key based on the request's parameters.
     *
     * @param Request $request
     * @return $this
     * @throws Exception
     */
    public function setKeyFromRequest(Request $request) : CacheHttpResponse
    {
        $this->key(md5("{$request->method}_{$request->buildUrlString()}"));

        return $this;
    }
}
