<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\API\Controllers;

use Exception;
use GoDaddy\WordPress\MWC\Common\API\Controllers\AbstractController;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Gateways\BusinessGateway;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Onboarding as Onboarding;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Producers\OnboardingEventsProducer;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPaymentsGateway;
use WP_Error;
use WP_REST_Response;

/**
 * GoDaddy Payments REST API route controller.
 */
class GoDaddyPaymentsController extends AbstractController implements ComponentContract
{
    /** @var string */
    protected $route = 'payments/godaddy-payments';

    /**
     * Loads the controller component.
     */
    public function load()
    {
        $this->registerRoutes();
    }

    /**
     * Registers the REST API routes.
     */
    public function registerRoutes()
    {
        register_rest_route($this->namespace, '/'.$this->route, [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'getItem'],
                'permission_callback' => [$this, 'getItemsPermissionsCheck'],
            ],
        ]);
    }

    /**
     * Gets the response item.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function getItem()
    {
        try {
            $response = [
                'appId'                 => Poynt::getAppId(),
                'businessId'            => Poynt::getBusinessId(),
                'features'              => $this->getAvailableFeatures(),
                'isEnabled'             => Poynt::isEnabled(),
                'status'                => $this->getStatus(),
                'accountSwitchNonceURL' => wp_nonce_url(add_query_arg('action', OnboardingEventsProducer::ACTION_SWITCH, admin_url('admin.php')), OnboardingEventsProducer::ACTION_SWITCH),
            ];

            // TODO: remove the feature flag check and always return these properties in MWC-5154 {@cwiseman 2022-04-05}
            if (true === Configuration::get('features.gdp_by_default.enabled')) {
                $response['business'] = ArrayHelper::get($response, 'businessId') ? BusinessGateway::getNewInstance()->get()->toArray() : null;
                $response['paymentMethods'] = [
                    'cards' => [
                        'isEnabled' => (bool) Configuration::get('payments.poynt.enabled', false),
                    ],
                    'sellingInPerson' => [
                        'isEnabled' => (bool) Configuration::get('payments.godaddy-payments-payinperson.enabled', false),
                    ],
                    'googlePay' => [
                        'isEnabled' => (bool) Configuration::get('payments.googlePay.enabled', false),
                    ],
                    'applePay' => [
                        'isEnabled' => (bool) Configuration::get('payments.applePay.enabled', false),
                    ],
                ];
                $response['hasTerminalActivated'] = (bool) Configuration::get('payments.godaddy-payments-payinperson.hasTerminalActivated', false);
            }
        } catch (Exception $exception) {
            $errorCode = $exception->getCode() ?: 500;
            $response = new WP_Error(
                $errorCode,
                /* translators: Placeholder: %s - error message */
                sprintf(__('Error: %s'), $exception->getMessage()),
                ['status' => $errorCode]
            );
        }

        return rest_ensure_response($response);
    }

    /**
     * Gets the gateway connection status.
     *
     * @return string|null
     * @throws Exception
     */
    protected function getStatus()
    {
        if (! GoDaddyPaymentsGateway::isActive()) {
            return 'UNAVAILABLE';
        }

        $status = Onboarding::getStatus();

        return ! empty($status) ? $status : null;
    }

    /**
     * Gets the payment gateway available features.
     *
     * @return array
     * @throws Exception
     */
    protected function getAvailableFeatures() : array
    {
        $features = [];

        if (Onboarding::hasBankAccount()) {
            $features[] = 'BANK_ACCOUNT';
        }
        if (Onboarding::depositsEnabled()) {
            $features[] = 'DEPOSITS';
        }
        if (Onboarding::paymentsEnabled()) {
            $features[] = 'PAYMENTS';
        }

        return $features;
    }

    /**
     * Determines whether the current user has permissions to get results.
     *
     * @return bool
     */
    public function getItemsPermissionsCheck() : bool
    {
        return current_user_can('manage_woocommerce');
    }

    /**
     * Gets the item schema.
     *
     * @return array
     */
    public function getItemSchema() : array
    {
        return [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'godaddyPayments',
            'type'       => 'object',
            'properties' => [
                'appId' => [
                    'description' => __('Application ID.', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'businessId' => [
                    'description' => __('Business ID.', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'features' => [
                    'description' => __('List of available features.', 'mwc-core'),
                    'type'        => 'array',
                    'items'       => [
                        'type' => 'string',
                        'enum' => [
                            'BANK_ACCOUNT',
                            'DEPOSITS',
                            'PAYMENTS',
                        ],
                    ],
                    'context'  => ['view', 'edit'],
                    'readonly' => true,
                ],
                'isEnabled' => [
                    'description' => __('Whether the payment gateway is enabled.', 'mwc-core'),
                    'type'        => 'boolean',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'status' => [
                    'description' => __('Payment gateway connection status.', 'mwc-core'),
                    'type'        => 'string',
                    'enum'        => [
                        Onboarding::STATUS_CONNECTED,
                        Onboarding::STATUS_CONNECTING,
                        Onboarding::STATUS_DECLINED,
                        Onboarding::STATUS_DISCONNECTED,
                        Onboarding::STATUS_INCOMPLETE,
                        Onboarding::STATUS_NEEDS_ATTENTION,
                        Onboarding::STATUS_PENDING,
                        Onboarding::STATUS_SUSPENDED,
                        Onboarding::STATUS_TERMINATED,
                        'UNAVAILABLE',
                    ],
                    'context'  => ['view', 'edit'],
                    'readonly' => true,
                ],
                'accountSwitchNonceURL' => [
                    'description' => __('Nonce for switching accounts.', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
            ],
        ];
    }
}
