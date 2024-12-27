<?php

namespace GoDaddy\WordPress\MWC\Core\API\Controllers\Platform;

use Exception;
use GoDaddy\WordPress\MWC\Common\API\Controllers\AbstractController;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;
use GoDaddy\WordPress\MWC\Dashboard\API\Controllers\Traits\RequiresWooCommercePermissionsTrait;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Controller for the endpoint handling commerce platform stores related to the channel.
 */
class StoresController extends AbstractController implements ComponentContract
{
    use RequiresWooCommercePermissionsTrait;

    /**
     * Route.
     *
     * @var string
     */
    protected $route = 'platform/stores';

    /**
     * Initializes the controller.
     *
     * @return void
     */
    public function load() : void
    {
        $this->registerRoutes();
    }

    /**
     * Registers endpoint routes.
     *
     * @return void
     */
    public function registerRoutes() : void
    {
        register_rest_route(
            $this->namespace,
            "/{$this->route}",
            [
                [
                    'methods'             => 'GET',
                    'callback'            => [$this, 'getItems'],
                    'permission_callback' => [$this, 'getItemsPermissionsCheck'],
                ],
                'schema' => [$this, 'getItemSchema'],
            ]
        );
    }

    /**
     * Returns a response with the stores available for the current channel.
     *
     * @internal
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response|WP_Error
     */
    public function getItems(WP_REST_Request $request)
    {
        try {
            $platform = PlatformRepositoryFactory::getNewInstance()->getPlatformRepository();
            $store = $platform->getStoreRepository();

            $response = $store->listStores($this->getQueryArgsFromRequest($request));
        } catch (Exception $exception) {
            new SentryException($exception->getMessage(), $exception);

            $response = new WP_Error('error_listing_stores', $exception->getMessage());
        }

        return rest_ensure_response($response);
    }

    /**
     * Gets the query args from the request, stripping out any we don't need.
     *
     * @param WP_REST_Request $request
     * @return array<string, mixed>|null
     */
    protected function getQueryArgsFromRequest(WP_REST_Request $request) : ?array
    {
        $queryArgs = $request->get_query_params();

        // this is the endpoint path, which we don't need - e.g. /wp-json/godaddy/mwc/v1/platform/stores
        unset($queryArgs['q']);

        return $queryArgs ?: null;
    }

    /**
     * Gets the endpoint item schema.
     *
     * @internal
     *
     * @return array<string, string|array<string, array<string, mixed>>>
     */
    public function getItemSchema() : array
    {
        return [
            '$schema'    => 'http://json-schema.org/draft-07/schema#',
            'title'      => 'storesList',
            'type'       => 'object',
            'properties' => [
                'businesses' => [
                    'type'        => 'array',
                    'description' => __('A list of businesses.', 'mwc-core'),
                    'items'       => [
                        'type'       => 'object',
                        'properties' => [
                            'businessId' => [
                                'type'        => 'string',
                                'format'      => 'uuid',
                                'description' => __('The unique identifier for the business.', 'mwc-core'),
                            ],
                            'legalName' => [
                                'type'        => 'string',
                                'description' => __('The legal name of the business.', 'mwc-core'),
                            ],
                            'stores' => [
                                'type'        => 'array',
                                'description' => __('A list of stores associated with the business.', 'mwc-core'),
                                'items'       => [
                                    'type'       => 'object',
                                    'properties' => [
                                        'displayName' => [
                                            'type'        => 'string',
                                            'description' => __('The display name of the store.', 'mwc-core'),
                                        ],
                                        'storeId' => [
                                            'type'        => 'string',
                                            'format'      => 'uuid',
                                            'description' => __('The unique identifier for the store.', 'mwc-core'),
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
