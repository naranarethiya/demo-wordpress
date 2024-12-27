<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\API\Controllers\GoDaddyPayments\Wallets;

use Exception;
use GoDaddy\WordPress\MWC\Common\API\Controllers\AbstractController;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ConditionalComponentContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\WooCommerceCartException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\ProductsRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Core\Payments\API\Traits\InitializesCartTrait;
use GoDaddy\WordPress\MWC\Core\Payments\API\Traits\VerifiesNonceTrait;
use GoDaddy\WordPress\MWC\Core\Payments\Exceptions\InvalidProductException;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters\SessionWalletRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\ProductLineObject;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\ApplePayGateway;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\GooglePayGateway;
use WC_Cart;
use WC_Product;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Payment request controller.
 */
class WalletRequestController extends AbstractController implements ConditionalComponentContract
{
    use InitializesCartTrait;
    use VerifiesNonceTrait;

    /** @var ProductLineObject[] product lines passed in by the request */
    protected $products = [];

    /** @var WC_Cart a temporary cart instance */
    protected $cart;

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
        register_rest_route($this->namespace, '/'.$this->route.'/request', [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'getPaymentRequest'],
                'permission_callback' => '__return_true',
                'schema'              => [$this, 'getItemSchema'],
            ],
        ]);
    }

    /**
     * Gets the payment request for the current customer.
     *
     * @return WP_Error|WP_REST_Response
     * @internal
     */
    public function getPaymentRequest(WP_REST_Request $request)
    {
        try {
            $this->initializeProducts($request);
            $this->initializeCart();
            $this->validateProducts();

            // In order to properly calculate totals and taxes for the products passed in by the request (which happens
            // on single product pages), we need to replace current cart contents with the given products.
            $this->maybeReplaceCart();

            $response = SessionWalletRequestAdapter::getNewInstance(WooCommerceRepository::getCartInstance())
                ->convertFromSource()
                ->toArray();

            // restore original cart
            $this->maybeRestoreCart();
        } catch (Exception $exception) {
            $response = $this->getPaymentRequestError($exception);
        }

        return rest_ensure_response($response);
    }

    /**
     * Replaces current cart contents with the products provided by the request, if any.
     *
     * @return void
     * @throws WooCommerceCartException|Exception
     */
    protected function maybeReplaceCart() : void
    {
        if (! $this->hasProductsFromRequest()) {
            return;
        }

        // store a copy of current cart state so that it can be restored later.
        $this->cart = clone ($cart = WooCommerceRepository::getCartInstance());

        $cart->empty_cart();

        foreach ($this->products as $productLine) {
            // Note: WC_cart::add_to_cart does actually support fractional (float) quantities, even though the method's
            // PHPDoc typehints `$quantity` as an integer
            // @phpstan-ignore-next-line
            $cart->add_to_cart($productLine->getProduct()->get_id(), $productLine->getQuantity());
        }

        $cart->calculate_fees();
        $cart->calculate_shipping();
        $cart->calculate_totals();
    }

    /**
     * Restores the original cart state if there were any products passed in by the request.
     *
     * @return void
     * @throws WooCommerceCartException
     */
    protected function maybeRestoreCart() : void
    {
        if (! $this->hasProductsFromRequest()) {
            return;
        }

        $cart = WooCommerceRepository::getCartInstance();

        $cart->empty_cart();
        $cart->set_cart_contents($this->cart->get_cart());
        $cart->set_totals($this->cart->get_totals());
        $cart->set_applied_coupons($this->cart->get_applied_coupons());
        $cart->set_coupon_discount_totals($this->cart->get_coupon_discount_totals());
        $cart->set_coupon_discount_tax_totals($this->cart->get_coupon_discount_tax_totals());
        $cart->set_removed_cart_contents($this->cart->get_removed_cart_contents());
        $cart->calculate_totals();
    }

    /**
     * Gets a payment request error.
     *
     * @param Exception $exception
     * @return WP_Error
     */
    protected function getPaymentRequestError(Exception $exception) : WP_Error
    {
        return new WP_Error(
            'UNKNOWN',
            $exception->getMessage(),
            [
                'status' => $exception instanceof InvalidProductException ? 400 : 500,
                'field'  => null,
            ]
        );
    }

    /**
     * Gets the item schema.
     *
     * @internal
     *
     * @return array<mixed>
     */
    public function getItemSchema() : array
    {
        return [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'status',
            'type'       => 'object',
            'properties' => [
                'total' => [
                    'description' => __('Order total, based on cart total.', 'mwc-core'),
                    'type'        => 'object',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                    'properties'  => [
                        'amount' => [
                            'type' => 'float',
                        ],
                        'label' => [
                            'type' => 'string',
                        ],
                    ],
                ],
                'country' => [
                    'description' => __('2-letter ISO 3166 country code.', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'currency' => [
                    'description' => __('3-letter ISO 4217 currency code.', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'merchantName' => [
                    'description' => __('Name of the merchant.', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'shippingType' => [
                    'description' => __('The shipping type based on the chosen shipping method.', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                    'enum'        => [
                        'delivery',
                        'pickup',
                        'shipping',
                    ],
                ],
                'shippingMethods' => [
                    'description' => __('Shipping methods for the payment request.', 'mwc-core'),
                    'type'        => 'array',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                    'items'       => [
                        'type' => 'string',
                    ],
                ],
                'lineItems' => [
                    'description' => __('Items in the order.', 'mwc-core'),
                    'type'        => 'array',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                    'items'       => [
                        'type'       => 'object',
                        'properties' => [
                            'amount' => [
                                'type' => 'float',
                            ],
                            'label' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                ],
                'requireEmail' => [
                    'description' => __('Whether to require customer email.', 'mwc-core'),
                    'type'        => 'boolean',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'requirePhone' => [
                    'description' => __('Whether to require customer phone.', 'mwc-core'),
                    'type'        => 'boolean',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'requireShippingAddress' => [
                    'description' => __('Whether to require customer shipping address.', 'mwc-core'),
                    'type'        => 'boolean',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'supportCouponCode' => [
                    'description' => __('Whether the customer should be allowed to enter coupons.', 'mwc-core'),
                    'type'        => 'boolean',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'disableWallets' => [
                    'description' => __('A list of wallets to disable.', 'mwc-core'),
                    'type'        => 'object',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                    'properties'  => [
                        'applePay' => [
                            'type' => 'boolean',
                        ],
                        'googlePay' => [
                            'type' => 'boolean',
                        ],
                    ],
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

    /**
     * Initializes products passed in by the request.
     *
     * @param WP_REST_Request $request
     * @return ProductLineObject[]
     * @throws Exception
     */
    protected function initializeProducts(WP_REST_Request $request) : array
    {
        $products = $request->get_param('products');

        if (empty($products)) {
            return [];
        }

        if (! ArrayHelper::accessible($products)) {
            throw new Exception(__('Invalid products data.', 'mwc-core'));
        }

        foreach ($products as $productData) {
            $product = ProductsRepository::get(TypeHelper::int(ArrayHelper::get($productData, 'id'), 0));

            if (! $product || ! $product->is_purchasable() || ! $product->is_in_stock()) {
                continue;
            }

            $this->products[] = ProductLineObject::getNewInstance()
                ->setProduct($product)
                ->setQuantity(TypeHelper::float(ArrayHelper::get($productData, 'quantity'), 1));
        }

        return $this->products;
    }

    /**
     * Checks whether there's at least one product passed in by the request.
     *
     * Products can be passed in the request when users click on the Pay Button for a single product, and we want to
     * ignore any products already added to cart.
     *
     * @return bool
     */
    protected function hasProductsFromRequest() : bool
    {
        return ! empty($this->products);
    }

    /**
     * Validates products for the current payment request.
     *
     * @throws Exception|InvalidProductException
     */
    protected function validateProducts() : void
    {
        if ($this->hasProductsFromRequest()) {
            foreach ($this->products as $productLine) {
                if (! $this->isProductSupported($productLine->getProduct())) {
                    throw new InvalidProductException('Product not supported.');
                }
            }
        } elseif (! $this->isCartSupported()) {
            throw new InvalidProductException('Cart contents not supported.');
        }
    }

    /**
     * Determines whether the given product is supported.
     *
     * @param WC_Product|int $product product or ID
     * @return bool
     */
    protected function isProductSupported($product) : bool
    {
        $supported = true;

        // no subscription products
        if (class_exists('WC_Subscriptions_Product') && \WC_Subscriptions_Product::is_subscription($product)) {
            $supported = false;
        }

        // no pre-order "charge upon release" products
        if (class_exists('WC_Pre_Orders_Product') && \WC_Pre_Orders_Product::product_is_charged_upon_release($product)) {
            $supported = false;
        }

        // no products with add-ons (on single product page only)
        if (class_exists('WC_Product_Addons_Helper') && ! empty(\WC_Product_Addons_Helper::get_product_addons($product))) {
            $supported = false;
        }

        return (bool) apply_filters('mwc_payments_wallet_product_is_supported', $supported, $product);
    }

    /**
     * Determines whether the current cart contents are supported.
     *
     * @return bool
     */
    protected function isCartSupported() : bool
    {
        $supported = true;

        if (class_exists('WC_Subscriptions_Cart') && \WC_Subscriptions_Cart::cart_contains_subscription()) {
            $supported = false;
        }

        if (class_exists('WC_Pre_Orders_Cart') && \WC_Pre_Orders_Cart::cart_contains_pre_order()) {
            $supported = false;
        }

        return (bool) apply_filters('mwc_payments_wallet_cart_contents_are_supported', $supported);
    }
}
