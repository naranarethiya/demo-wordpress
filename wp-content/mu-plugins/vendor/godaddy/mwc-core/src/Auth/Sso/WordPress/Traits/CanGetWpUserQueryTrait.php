<?php

namespace GoDaddy\WordPress\MWC\Core\Auth\Sso\WordPress\Traits;

use WP_User_Query;

/**
 * Trait for getting a WP_User_Query instance.
 */
trait CanGetWpUserQueryTrait
{
    /**
     * Gets a WordPress User Query instance for given arguments.
     *
     * @param array<string, mixed> $args
     * @return WP_User_Query
     */
    public function getWpUserQuery(array $args) : WP_User_Query
    {
        // The @var annotation below is used to trick PHPStan into thinking that the
        // given array of args has shape define in the stub for WP_User_Query::__construct()
        // that we get from the php-stubs/wordpress-stubs package.

        /** @var array{offset?: int} $query */
        $query = $args;

        return new WP_User_Query($query);
    }
}
