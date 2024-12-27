<?php

namespace GoDaddy\WordPress\MWC\Core\API\Controllers;

use Exception;
use GoDaddy\WordPress\MWC\Common\API\Controllers\AbstractController;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Platforms\Contracts\PlatformRepositoryContract;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;
use WP_Error;
use WP_REST_Response;

/**
 * AccountController controller class.
 */
class AccountController extends AbstractController implements ComponentContract
{
    /**
     * AccountController constructor.
     */
    public function __construct()
    {
        $this->route = 'account';
    }

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
     * Registers the API routes for the endpoints provided by the controller.
     */
    public function registerRoutes() : void
    {
        register_rest_route(
            $this->namespace, "/{$this->route}", [
                [
                    'methods'             => 'GET', // \WP_REST_Server::READABLE,
                    'callback'            => [$this, 'getItem'],
                    'permission_callback' => [$this, 'getItemsPermissionsCheck'],
                ],
                'schema' => [$this, 'getItemSchema'],
            ]
        );
    }

    /**
     * Gets the account information.
     *
     * @internal
     *
     * @return WP_REST_Response|WP_Error
     * @throws Exception
     */
    public function getItem()
    {
        $platformRepository = PlatformRepositoryFactory::getNewInstance()->getPlatformRepository();

        $hostingPlanName = $this->getHostingPlanName($platformRepository);

        return rest_ensure_response([
            'account' => [
                'privateLabelId'       => (int) $platformRepository->getResellerId() ?: null,
                'isVersioningManual'   => (bool) Configuration::get('features.extensions.versionSelect'),
                'isOnResellerAccount'  => $platformRepository->isReseller(),
                'managedWordPressPlan' => $hostingPlanName,
                'plan'                 => $hostingPlanName,
                'platform'             => $platformRepository->getPlatformName(),
                'customerId'           => $platformRepository->getGoDaddyCustomerId(),
                'federationPartnerId'  => $platformRepository->getGoDaddyCustomer()->getFederationPartnerId(),
            ],
        ]);
    }

    /**
     * Gets the name of the hosting plan.
     *
     * The frontend expects to receive `null` when the hosting plan name is unknown.
     *
     * @param PlatformRepositoryContract $platformRepository
     * @return string|null
     */
    protected function getHostingPlanName(PlatformRepositoryContract $platformRepository) : ?string
    {
        return $platformRepository->getPlan()->getName() ?: null;
    }

    /**
     * Gets the schema for REST items provided by the controller.
     *
     * @return array<string, mixed>
     */
    public function getItemSchema() : array
    {
        return [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'account',
            'type'       => 'object',
            'properties' => [
                'privateLabelId' => [
                    'description' => __('The reseller private label ID (1 means GoDaddy, so not a reseller).', 'mwc-core'),
                    'type'        => 'int',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'isVersioningManual' => [
                    'description' => __('Whether the account can manually switch between extension versions.', 'mwc-core'),
                    'type'        => 'bool',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'isOnResellerAccount' => [
                    'description' => __('Whether or not the site is sold by a reseller.', 'mwc-core'),
                    'type'        => 'bool',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'plan' => [
                    'description' => __('The product plan the given account or site has purchased', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view'],
                    'readonly'    => true,
                ],
                'platform' => [
                    'description' => __('The hosting platform the given account or site is running on', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view'],
                    'readonly'    => true,
                ],
                'customerId' => [
                    'description' => __('The ID of the customer', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view'],
                    'readonly'    => true,
                ],
                'federationPartnerId' => [
                    'description' => __('The ID of the Federation Partner', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view'],
                    'readonly'    => true,
                ],
            ],
        ];
    }
}
