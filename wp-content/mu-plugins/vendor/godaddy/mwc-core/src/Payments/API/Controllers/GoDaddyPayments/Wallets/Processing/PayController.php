<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\API\Controllers\GoDaddyPayments\Wallets\Processing;

use Exception;
use GoDaddy\WordPress\MWC\Common\API\Controllers\AbstractController;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ConditionalComponentContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\WooCommerceCartException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\SanitizationHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Core\Payments\API;
use GoDaddy\WordPress\MWC\Core\Payments\API\Exceptions\MissingNonceException;
use GoDaddy\WordPress\MWC\Core\Payments\API\Traits\InitializesCartTrait;
use GoDaddy\WordPress\MWC\Core\Payments\API\Traits\VerifiesNonceTrait;
use GoDaddy\WordPress\MWC\Core\Payments\DataSources\WooCommerce\Adapters\CartOrderAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\Exceptions\InvalidNonceException;
use GoDaddy\WordPress\MWC\Core\Payments\Exceptions\InvalidPaymentMethodException;
use GoDaddy\WordPress\MWC\Core\Payments\Exceptions\MissingSourceException;
use GoDaddy\WordPress\MWC\Core\Payments\Exceptions\WooCommercePaymentFailedException;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\OrderAdapter;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\CorePaymentGateways;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\ApplePayGateway;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\GooglePayGateway;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPaymentsGateway;
use WC_Order;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Controller for submitting a payment with Apple Pay.
 */
class PayController extends AbstractController implements ConditionalComponentContract
{
    use InitializesCartTrait;
    use VerifiesNonceTrait;

    /**
     * Sets the endpoint route.
     */
    public function __construct()
    {
        $this->route = 'payments/godaddy-payments/wallets';
    }

    /**
     * Loads the component and registers the endpoint routes.
     */
    public function load() : void
    {
        $this->registerRoutes();
    }

    /**
     * Registers the endpoint routes.
     */
    public function registerRoutes() : void
    {
        register_rest_route($this->namespace, '/'.$this->route.'/processing/pay', [
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'pay'],
                'permission_callback' => '__return_true',
                'args'                => $this->getPayArgs(),
                'schema'              => [$this, 'getItemSchema'],
            ],
        ]);
    }

    /**
     * Gets the arguments for the pay endpoint.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function getPayArgs() : array
    {
        return [
            'nonce' => [
                'required' => true,
                'type'     => 'string',
            ],
            'shouldTokenize' => [
                'required' => false,
                'type'     => 'boolean',
            ],
        ];
    }

    /**
     * Gets the payment request for the current customer.
     *
     * The logic of this method will set some POST variables that will be handled by the GoDaddy Payments gateway:
     * @see GoDaddyPaymentsGateway::getPaymentMethodForAdd() for the nonce
     * @see GoDaddyPaymentsGateway::getTransactionForPayment() for the tokenization flag
     *
     * @internal
     *
     * @param WP_REST_Request $request
     * @return WP_Error|WP_REST_Response
     */
    public function pay(WP_REST_Request $request)
    {
        try {
            $this->verifyNonce($request, API::NONCE_ACTION);
            $this->initializeCart();

            $nonce = SanitizationHelper::textField((string) $request->get_param('nonce'));

            if (empty($nonce) || ! is_string($nonce)) {
                throw new MissingNonceException('Missing nonce');
            }

            /* @var GoDaddyPaymentsGateway $gateway */
            if (! $gateway = CorePaymentGateways::getManagedPaymentGatewayInstance('poynt')) {
                throw new InvalidPaymentMethodException('GoDaddy Payments payment gateway not found');
            }

            if (! $source = $request->get_param('source')) {
                throw new MissingSourceException('Missing payment source');
            }

            $order = $this->createOrderFromCart($source);

            $order->set_payment_method('poynt');
            $order->save();

            $_POST['mwc-payments-poynt-payment-nonce'] = $nonce;
            $_POST['mwc-payments-poynt-tokenize-payment-method'] = (bool) $request->get_param('shouldTokenize');

            $this->throwOnFailedTransaction($responseData = $gateway->process_payment($order->get_id()));

            $response = [
                'orderId'     => $order->get_id(),
                'redirectUrl' => ArrayHelper::get($responseData, 'redirect'),
            ];
        } catch (InvalidNonceException $exception) {
            $response = new WP_Error('INVALID_NONCE', $exception->getMessage(), [
                'status' => $exception->getCode(),
            ]);
        } catch (Exception $exception) {
            $response = new WP_Error('PAYMENT_FAILED', $exception->getMessage(), [
                'status' => $exception->getCode() ?: 400,
                'field'  => null,
            ]);
        }

        return rest_ensure_response($response);
    }

    /**
     * Throws an exception if the transaction failed.
     *
     * @param array<mixed> $responseData
     * @return void
     * @throws WooCommercePaymentFailedException
     */
    protected function throwOnFailedTransaction(array $responseData = []) : void
    {
        if (! ArrayHelper::get($responseData, 'redirect') || 'success' !== ArrayHelper::get($responseData, 'result')) {
            $errorMessage = ArrayHelper::get($responseData, 'message');

            // get the detailed error message from WC notices, if available
            // implementation based on \Automattic\WooCommerce\Blocks\StoreApi\Utilities\NoticeHandler
            if (count($error_notices = wc_get_notices('error'))) {
                $errorMessage = wp_strip_all_tags(current($error_notices)['notice']);

                // Prevent notices from being output later on.
                wc_clear_notices();
            }

            throw new WooCommercePaymentFailedException($errorMessage ?? 'Unknown error');
        }
    }

    /**
     * Creates an order from the cart.
     *
     * @param string $source
     * @return WC_Order
     * @throws WooCommerceCartException
     * @throws Exception
     */
    protected function createOrderFromCart(string $source) : WC_Order
    {
        $wcOrder = $this->getSourceOrder($this->getCartOrder($source));
        $wcOrder->save();

        return $wcOrder;
    }

    /**
     * Gets a WooCommerce order from a native order.
     *
     * @param Order $order
     * @return WC_Order
     * @throws Exception
     */
    protected function getSourceOrder(Order $order) : WC_Order
    {
        return (new OrderAdapter(new WC_Order()))->convertToSource($order);
    }

    /**
     * Gets an order from the cart.
     *
     * @param string $source
     * @return Order
     * @throws WooCommerceCartException|Exception
     */
    protected function getCartOrder(string $source) : Order
    {
        return (new CartOrderAdapter(WooCommerceRepository::getCartInstance()))->convertFromSource([], ['_created_via' => "mwc_payments_{$source}"]);
    }

    /**
     * Determines whether the given route should be authenticated by nonce verification.
     *
     * @param string $route
     * @return bool
     */
    public function shouldAuthenticateRouteByNonceVerification(string $route) : bool
    {
        return 0 === strpos($route, $this->namespace.'/'.$this->route.'/processing/pay');
    }

    /**
     * Gets the item schema.
     *
     * @internal
     *
     * @return array<string, mixed>
     */
    public function getItemSchema() : array
    {
        return [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'status',
            'type'       => 'object',
            'properties' => [
                'orderId' => [
                    'description' => __('The order ID.', 'mwc-core'),
                    'type'        => 'integer',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'redirectUrl' => [
                    'description' => __('The URL to redirect the customer to.', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
            ],
        ];
    }

    /**
     * Determines whether the component should load.
     *
     * @return bool
     * @throws Exception
     */
    public static function shouldLoad() : bool
    {
        return ApplePayGateway::isActive() || GooglePayGateway::isActive();
    }
}
