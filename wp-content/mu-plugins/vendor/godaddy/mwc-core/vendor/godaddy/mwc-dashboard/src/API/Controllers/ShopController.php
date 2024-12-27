<?php

namespace GoDaddy\WordPress\MWC\Dashboard\API\Controllers;

use Exception;
use GoDaddy\WordPress\MWC\Common\API\Controllers\AbstractController;
use GoDaddy\WordPress\MWC\Common\API\Response;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\DataSources\Request\Adapters\AddressAdapter;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\AddressHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\Address;
use GoDaddy\WordPress\MWC\Common\Models\User;
use GoDaddy\WordPress\MWC\Common\Platforms\Exceptions\PlatformRepositoryException;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\TemplatesRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\SiteRepository;
use GoDaddy\WordPress\MWC\Common\Stores\Exceptions\StoreRepositoryException;
use GoDaddy\WordPress\MWC\Dashboard\Repositories\UserRepository;
use GoDaddy\WordPress\MWC\Dashboard\Support\Support;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * ShopController controller class.
 */
class ShopController extends AbstractController
{
    /**
     * Controller constructor.
     */
    public function __construct()
    {
        $this->route = 'shop';
    }

    /**
     * Registers the API routes for the endpoints provided by the controller.
     *
     * @return void
     */
    public function registerRoutes() : void
    {
        register_rest_route(
            $this->namespace, "/{$this->route}", [
                [
                    'methods' => 'GET',
                    /* @see WP_REST_Server::READABLE */
                    'callback'            => [$this, 'getItem'],
                    'permission_callback' => [$this, 'getItemsPermissionsCheck'],
                ],
                [
                    'methods'             => 'PATCH',
                    'callback'            => [$this, 'updateItem'],
                    'permission_callback' => [$this, 'updateItemPermissionsCheck'],
                    'args'                => [
                        'defaultStoreId' => [
                            'required'    => true,
                            'description' => __('The default store ID to set for the current channel', 'mwc-dashboard'),
                            'type'        => 'string',
                            'context'     => ['view', 'edit'],
                        ],
                        'businessId' => [
                            'required'    => true,
                            'description' => __('The business ID', 'mwc-dashboard'),
                            'type'        => 'string',
                            'context'     => ['view', 'edit'],
                        ],
                    ],
                ],
                'schema' => [$this, 'getItemSchema'],
            ]
        );
    }

    /**
     * Sends a response to the endpoint GET request with the shop information.
     *
     * @internal
     *
     * @return WP_REST_Response|WP_Error
     * @throws Exception
     */
    public function getItem()
    {
        $adminUser = User::getCurrent();
        $supportUser = null;

        if ($supportUserEmail = Configuration::get('support.support_user.email')) {
            $supportUser = User::getByEmail(TypeHelper::string($supportUserEmail, ''));
        }

        if (! $supportUser && ($supportUserLogin = Configuration::get('support.support_user.login'))) {
            $supportUser = User::getByHandle(TypeHelper::string($supportUserLogin, ''));
        }

        $platform = PlatformRepositoryFactory::getNewInstance()->getPlatformRepository();
        $store = $platform->getStoreRepository();

        $originAddress = $this->getOriginAddress();

        $item = [
            'shop' => [
                'siteId'                                   => $platform->getSiteId(),
                'siteURL'                                  => SiteRepository::getSiteUrl(),
                'defaultStoreId'                           => $store->getStoreId() ?? '',
                'adminEmail'                               => $adminUser ? $adminUser->getEmail() : '',
                'firstAdminLoginTimestamp'                 => $this->getFirstAdminLoginTimestamp(),
                'supportUserEmail'                         => $supportUser ? $supportUser->getEmail() : '',
                'supportBotConnected'                      => Support::isSupportConnected(),
                'woocommerceConnected'                     => WooCommerceRepository::isWooCommerceConnected(),
                'dashboardType'                            => $platform->hasEcommercePlan() ? 'MWC' : '',
                'isReseller'                               => $platform->isReseller(),
                'privateLabelId'                           => $platform->getResellerId(),
                'supportBotConnectUrl'                     => Support::getConnectUrl(),
                'isCurrentUserOptedInForDashboardMessages' => UserRepository::userOptedInForDashboardMessages(),
                'createdAt'                                => $this->getShopCreatedAt(),
                'location'                                 => $this->getShopLocation(),
                'originAddress'                            => $this->getAddressData($originAddress),
                'originAddressFormatted'                   => AddressHelper::format($originAddress),
                'shouldRecommendGoDaddyPayments'           => $this->shouldRecommendGoDaddyPayments(),
                // @TODO Utilize core EmailNotifications::isActive() once merged into core {ssmith1 2021-09-07}
                'isEmailNotificationsFeatureActive' => static::isEmailNotificationsFeatureActive(),
                // @TODO Utilize core EmailNotifications::isEnabled() once merged into core {ssmith1 2021-09-07}
                'isEmailNotificationsFeatureEnabled' => static::isEmailNotificationsFeatureActive() && Configuration::get('email_notifications.enabled', false),
                'isEmailVerificationFeatureActive'   => static::isEmailVerificationFeatureActive(),
                'isOnlyVirtual'                      => $this->isSellingOnlyDigitalProducts(),
                'hasEmailTemplateOverrides'          => $this->hasEmailTemplateOverrides(),
                'settings'                           => $this->getShopSettings(),
                'permalinkStructure'                 => $this->getPermalinkStructure(),
            ],
        ];

        return rest_ensure_response($item);
    }

    /**
     * Determines whether the Email Notifications feature is available.
     *
     * @TODO this is a duplicate of MWC Core EmailNotifications::isActive, which we can't use from the Dashboard until these projects have been merged {unfulvio 2021-10-25}
     *
     * @return bool
     * @throws PlatformRepositoryException
     */
    public static function isEmailNotificationsFeatureActive() : bool
    {
        return Configuration::get('features.email_notifications', true)
            && PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->hasEcommercePlan()
            && WooCommerceRepository::isWooCommerceActive()
            && ManagedWooCommerceRepository::isAllowedToUseNativeFeatures();
    }

    /**
     * Determines if email verification is active.
     *
     * @TODO Remove when rollout constraints are removed {JO: 2021-10-25}
     *
     * @return bool
     * @throws Exception
     */
    public static function isEmailVerificationFeatureActive() : bool
    {
        return static::isEmailNotificationsFeatureActive();
    }

    /**
     * Updates the default store ID.
     *
     * @internal
     *
     * @param WP_REST_Request $request
     * @return void
     * @throws Exception
     */
    public function updateItem(WP_REST_Request $request) : void
    {
        try {
            $defaultStoreId = $request->get_param('defaultStoreId');
            $businessId = $request->get_param('businessId');

            if (empty($defaultStoreId) || ! is_string($defaultStoreId)) {
                throw new StoreRepositoryException('The default store ID must be a non-empty string');
            }

            if (empty($businessId) || ! is_string($businessId)) {
                throw new StoreRepositoryException('The business ID must be a non-empty string');
            }

            $platform = PlatformRepositoryFactory::getNewInstance()->getPlatformRepository();
            $store = $platform->getStoreRepository();

            // store the default store ID locally only if remote registration is successful (no exceptions thrown)
            $store->registerStore($defaultStoreId, $businessId);
            $store->setDefaultStoreId($defaultStoreId);

            Response::getNewInstance()->success()->send();
        } catch (Exception $exception) {
            new SentryException($exception->getMessage(), $exception);

            Response::getNewInstance()
                ->error([$exception->getMessage()], 400)
                ->send();
        }
    }

    /**
     * Returns the schema for REST items provided by the controller.
     *
     * @return array<string, string|array<string, array<string, mixed>>>
     */
    public function getItemSchema() : array
    {
        return [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'shop',
            'type'       => 'object',
            'properties' => [
                'siteId' => [
                    'description' => __('Site ID.', 'mwc-dashboard'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'siteUrl' => [
                    'description' => __('Site URL.', 'mwc-dashboard'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'defaultStoreId' => [
                    'description' => __('Default store ID.', 'mwc-dashboard'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => false,
                ],
                'adminEmail' => [
                    'description' => __('Current admin user\'s email.', 'mwc-dashboard'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'supportUserEmail' => [
                    'description' => __('Support user\'s email, if a support user exists.', 'mwc-dashboard'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'supportBotConnected' => [
                    'description' => __('Whether or not the site is connected to the support bot.', 'mwc-dashboard'),
                    'type'        => 'bool',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'woocommerceConnected' => [
                    'description' => __('Whether or not the site is connected to WooCommerce.com.', 'mwc-dashboard'),
                    'type'        => 'bool',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'dashboardType' => [
                    'description' => __('Dashboard type (MWC or empty).', 'mwc-dashboard'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'isReseller' => [
                    'description' => __('Whether or not the site is sold by a reseller.', 'mwc-dashboard'),
                    'type'        => 'bool',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'privateLabelId' => [
                    'description' => __('The reseller private label ID (1 means GoDaddy, so not a reseller).', 'mwc-dashboard'),
                    'type'        => 'int',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'supportBotConnectUrl' => [
                    'description' => __('URL to connect the site to the support bot.', 'mwc-dashboard'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'isCurrentUserOptedInForDashboardMessages' => [
                    'description' => __('Whether or not the current user is opted in to receive MWC Dashboard messages.', 'mwc-dashboard'),
                    'type'        => 'bool',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'createdAt' => [
                    'description' => __('The Shop page\'s creation date.', 'mwc-dashboard'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'location' => [
                    'type'       => 'object',
                    'properties' => [
                        'address1' => [
                            'description' => __('Address line 1', 'mwc-dashboard'),
                            'type'        => 'string',
                            'context'     => ['view', 'edit'],
                            'readonly'    => true,
                        ],
                        'address2' => [
                            'description' => __('Address line 2', 'mwc-dashboard'),
                            'type'        => 'string',
                            'context'     => ['view', 'edit'],
                            'readonly'    => true,
                        ],
                        'city' => [
                            'description' => __('City', 'mwc-dashboard'),
                            'type'        => 'string',
                            'context'     => ['view', 'edit'],
                            'readonly'    => true,
                        ],
                        'country' => [
                            'description' => __('Country', 'mwc-dashboard'),
                            'type'        => 'string',
                            'context'     => ['view', 'edit'],
                            'readonly'    => true,
                        ],
                        'state' => [
                            'description' => __('State', 'mwc-dashboard'),
                            'type'        => 'string',
                            'context'     => ['view', 'edit'],
                            'readonly'    => true,
                        ],
                        'postalCode' => [
                            'description' => __('Postal code', 'mwc-dashboard'),
                            'type'        => 'string',
                            'context'     => ['view', 'edit'],
                            'readonly'    => true,
                        ],
                    ],
                ],
                'originAddress' => [
                    'type'       => 'object',
                    'properties' => [
                        'administrativeDistricts' => [
                            'description' => __('Administrative districts', 'mwc-dashboard'),
                            'type'        => 'array',
                            'context'     => ['view', 'edit'],
                            'readonly'    => true,
                        ],
                        'businessName' => [
                            'description' => __('Business name', 'mwc-dashboard'),
                            'type'        => 'string',
                            'context'     => ['view', 'edit'],
                            'readonly'    => true,
                        ],
                        'countryCode' => [
                            'description' => __('Country code', 'mwc-dashboard'),
                            'type'        => 'string',
                            'context'     => ['view', 'edit'],
                            'readonly'    => true,
                        ],
                        'firstName' => [
                            'description' => __('First name', 'mwc-dashboard'),
                            'type'        => 'string',
                            'context'     => ['view', 'edit'],
                            'readonly'    => true,
                        ],
                        'lastName' => [
                            'description' => __('Last name', 'mwc-dashboard'),
                            'type'        => 'string',
                            'context'     => ['view', 'edit'],
                            'readonly'    => true,
                        ],
                        'lines' => [
                            'description' => __('Address lines', 'mwc-dashboard'),
                            'type'        => 'array',
                            'context'     => ['view', 'edit'],
                            'readonly'    => true,
                        ],
                        'locality' => [
                            'description' => __('City', 'mwc-dashboard'),
                            'type'        => 'string',
                            'context'     => ['view', 'edit'],
                            'readonly'    => true,
                        ],
                        'phoneNumber' => [
                            'description' => __('Phone number', 'mwc-dashboard'),
                            'type'        => 'string',
                            'context'     => ['view', 'edit'],
                            'readonly'    => true,
                        ],
                        'postalCode' => [
                            'description' => __('Postal code', 'mwc-dashboard'),
                            'type'        => 'string',
                            'context'     => ['view', 'edit'],
                            'readonly'    => true,
                        ],
                        'subLocalities' => [
                            'description' => __('Sub localities', 'mwc-dashboard'),
                            'type'        => 'array',
                            'context'     => ['view', 'edit'],
                            'readonly'    => true,
                        ],
                    ],
                ],
                'originAddressFormatted' => [
                    'description' => __('Formatted address', 'mwc-dashboard'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'isEmailNotificationsFeatureActive' => [
                    'description' => __('Whether or not the site email notifications feature is active.', 'mwc-dashboard'),
                    'type'        => 'bool',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'isEmailNotificationsFeatureEnabled' => [
                    'description' => __('Whether or not the site email notifications feature is enabled.', 'mwc-dashboard'),
                    'type'        => 'bool',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'hasEmailTemplateOverrides' => [
                    'description' => __('Whether the site is currently overriding any of the WooCommerce email templates.', 'mwc-dashboard'),
                    'type'        => 'bool',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'settings' => [
                    'type'       => 'object',
                    'properties' => [
                        'weightUnits' => [
                            'description' => __('Configured weight unit', 'mwc-dashboard'),
                            'type'        => 'string',
                            'context'     => ['view', 'edit'],
                            'readonly'    => true,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Gets the timestamp for the first time that an administrator logged into the admin dashboard on the Managed WooCommerce platform.
     *
     * @return int
     */
    protected function getFirstAdminLoginTimestamp() : int
    {
        return (int) get_option('gd_system_first_login', 0);
    }

    /**
     * Gets the created at date for WooCommerce's shop page.
     *
     * @return string
     */
    private function getShopCreatedAt()
    {
        if (! function_exists('wc_get_page_id')) {
            return '';
        }

        if (! $shopPage = get_post(wc_get_page_id('shop'))) {
            return '';
        }

        return $shopPage->post_date;
    }

    /**
     * Gets the store location from WooCommerce settings.
     *
     * @return string
     */
    private function getShopLocation() : array
    {
        if (! function_exists('WC')) {
            return [];
        }

        return [
            'address1'   => WC()->countries->get_base_address(),
            'address2'   => WC()->countries->get_base_address_2(),
            'city'       => WC()->countries->get_base_city(),
            'country'    => WC()->countries->get_base_country(),
            'state'      => WC()->countries->get_base_state(),
            'postalCode' => WC()->countries->get_base_postcode(),
        ];
    }

    /**
     * Gets the origin address from WooCommerce settings.
     *
     * @return Address
     */
    protected function getOriginAddress() : Address
    {
        if (! WooCommerceRepository::isWooCommerceActive()) {
            return (new Address())->setBusinessName(SiteRepository::getTitle());
        }

        $adapter = AddressAdapter::getNewInstance([
            'businessName' => SiteRepository::getTitle(),
            'lines'        => [
                WC()->countries->get_base_address(),
                WC()->countries->get_base_address_2(),
            ],
            'locality'                => WC()->countries->get_base_city(),
            'administrativeDistricts' => [WC()->countries->get_base_state()],
            'postalCode'              => WC()->countries->get_base_postcode(),
            'countryCode'             => WooCommerceRepository::getBaseCountry(),
            'phoneNumber'             => get_option('mwc_store_phone', ''),
            'subLocalities'           => [],
        ]);

        return $adapter->convertFromSource();
    }

    /**
     * Gets response data for the given {@see Address} instance.
     *
     * @return array<string,array<int, string>|string|null>|null
     */
    protected function getAddressData(Address $address) : ?array
    {
        $data = AddressAdapter::getNewInstance([])->convertToSource($address);

        if (null === $data) {
            return null;
        }

        $phoneNumber = ArrayHelper::get($data, 'phoneNumber');

        if (is_string($phoneNumber)) {
            ArrayHelper::set(
                $data,
                'phoneNumber',
                preg_replace('/[^+0-9]/', '', $phoneNumber)
            );
        }

        return array_map(static function ($value) {
            return is_string($value) && empty($value) ? null : $value;
        }, $data);
    }

    /**
     * Gets a formatted string representation of the given address.
     *
     * @param Address $address
     * @return string|null
     */
    protected function getAddressFormatted(Address $address) : ?string
    {
        return AddressHelper::format($address) ?: null;
    }

    /**
     * Decides whether we should recommend GoDaddy Payments to the current site.
     *
     * @NOTE When we merge mwc-dashboard package into mwc-core, this method can be simplified to: GoDaddyPaymentsGateway::isActive() && empty(Poynt::getServiceId()) {llessa 2021-08-04}
     *
     * @return bool
     * @throws PlatformRepositoryException
     */
    protected function shouldRecommendGoDaddyPayments() : bool
    {
        $platformRepositoryFactory = PlatformRepositoryFactory::getNewInstance();
        $supportedCountries = TypeHelper::array(Configuration::get('features.godaddy_payments.supportedCountries'), []);
        $supportedCurrencies = TypeHelper::array(ArrayHelper::get($supportedCountries, WooCommerceRepository::getBaseCountry()), []);

        return ! $platformRepositoryFactory->getPlatformRepository()->isReseller()
            && $platformRepositoryFactory->getPlatformRepository()->getGoDaddyCustomer()->getFederationPartnerId() !== 'WORLDPAY'
            && empty(Configuration::get('payments.poynt.serviceId'))
            && ArrayHelper::has($supportedCountries, WooCommerceRepository::getBaseCountry())
            && ArrayHelper::contains($supportedCurrencies, WooCommerceRepository::getCurrency());
    }

    /**
     * Determines whether the store is selling digiral products only.
     *
     * We consider that a store sells digital products only if the merchant didn't
     * select physical_goods as one of the features during the WPNUX on-boarding experience.
     *
     * @return bool
     */
    protected function isSellingOnlyDigitalProducts() : bool
    {
        if (! ManagedWooCommerceRepository::hasCompletedWPNuxOnboarding()) {
            return false;
        }

        $features = $this->getWPNuxOnboardingFeatures();

        if (! ArrayHelper::accessible($features)) {
            return false;
        }

        return ! ArrayHelper::contains($features, 'physical_goods');
    }

    /**
     * Gets the features selected data added by the WPNux template on-boarding system.
     *
     * @return array|null
     */
    protected function getWPNuxOnboardingFeatures()
    {
        return ArrayHelper::get(static::getWPNuxOnboardingData(), '_meta.features');
    }

    /**
     * Gets the export data added by the WPNux template on-boarding system.
     *
     * @return array
     */
    protected static function getWPNuxOnboardingData() : array
    {
        return ManagedWooCommerceRepository::hasCompletedWPNuxOnboarding() ? ArrayHelper::wrap(json_decode(get_option('wpnux_export_data'), true)) : [];
    }

    /**
     * Determines whether the installation is overriding any of the WooCommerce email templates.
     *
     * @return bool
     * @throws Exception
     */
    private function hasEmailTemplateOverrides() : bool
    {
        return ! empty(TemplatesRepository::getEmailTemplateOverrides());
    }

    /**
     * Gets an associative array of shop settings.
     *
     * @return array<string, mixed>
     */
    protected function getShopSettings() : array
    {
        return [
            'weightUnits' => WooCommerceRepository::getWeightUnit(),
        ];
    }

    /**
     * Gets the current permalink structure.
     *
     * @return string
     */
    protected function getPermalinkStructure() : string
    {
        $defaultStructures = [
            'day-and-name'   => '/%year%/%monthnum%/%day%/%postname%/',
            'month-and-name' => '/%year%/%monthnum%/%postname%/',
            'numeric'        => '/'._x('archives', 'sample permalink base').'/%post_id%',
            'postname'       => '/%postname%/',
        ];

        $permalinkStructure = TypeHelper::string(get_option('permalink_structure'), '');

        if ($permalinkStructure === '') {
            return 'plain';
        }

        if ($structure = ArrayHelper::where($defaultStructures, function ($structure) use ($permalinkStructure) {
            return str_ends_with($permalinkStructure, $structure);
        })) {
            return key($structure);
        }

        return 'custom';
    }
}
