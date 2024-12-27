<?php

namespace GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce;

use Closure;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\RestApiRepository as WordPressRestApiRepository;

/**
 * A repository for handling WooCommerce REST API.
 */
class RestApiRepository
{
    /** @var array<string, mixed> */
    protected static array $cache = [];

    /**
     * Get an item from the cache, or executes the given callable and store the result.
     *
     * @param string $key
     * @param Closure $loader
     * @return mixed
     */
    protected static function cacheRemember(string $key, Closure $loader)
    {
        return static::$cache[$key] ??= $loader();
    }

    /**
     * Gets WooCommerce system status data.
     *
     * @return mixed[]
     */
    public static function getSystemStatus() : array
    {
        $loader = static fn () : ?array => WordPressRestApiRepository::getEndpointData('/wc/v3/system_status');

        return TypeHelper::array(static::cacheRemember('system_status', $loader), []);
    }
}
