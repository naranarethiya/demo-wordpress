<?php

namespace GoDaddy\WordPress\MWC\Common\Repositories\WordPress;

use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Enums\RequestMethodEnum;
use WP_REST_Request;
use WP_REST_Server;

/**
 * A repository for handling WordPress REST API.
 */
class RestApiRepository
{
    /**
     * Gets WordPress REST API server instance.
     *
     * @return WP_REST_Server|null
     */
    public static function getRestServer() : ?WP_REST_Server
    {
        $server = function_exists('rest_get_server') ? rest_get_server() : null;

        return $server instanceof WP_REST_Server ? $server : null;
    }

    /**
     * Creates a WordPress REST Request instance for the given route and method.
     *
     * @param string $route
     * @param RequestMethodEnum::* $method
     * @return WP_REST_Request<mixed[]>|null
     */
    public static function makeRestRequest(string $route, string $method = RequestMethodEnum::Get) : ?WP_REST_Request
    {
        if (! class_exists('WP_REST_Request')) {
            return null;
        }

        /** @var WP_REST_Request<mixed[]> */
        $request = new WP_REST_Request($method, $route);

        return $request;
    }

    /**
     * Gets REST response data for the given endpoint.
     *
     * @param string $endpoint
     * @param RequestMethodEnum::* $method
     * @param array<string, mixed>|null $queryParameters
     * @return array<mixed>|null
     */
    public static function getEndpointData(
        string $endpoint,
        string $method = RequestMethodEnum::Get,
        ?array $queryParameters = null) : ?array
    {
        if (! function_exists('rest_do_request')) {
            return null;
        }

        if (! $request = static::makeRestRequest($endpoint, $method)) {
            return null;
        }

        if ($queryParameters) {
            $request->set_query_params($queryParameters);
        }

        if (! $server = static::getRestServer()) {
            return null;
        }

        $response = rest_do_request($request);

        return TypeHelper::array($server->response_to_data($response, false), []);
    }
}
