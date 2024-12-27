<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\API\Controllers;

use Exception;
use GoDaddy\WordPress\MWC\Common\API\Controllers\AbstractController;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ConditionalComponentContract;
use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\AddressAdapter;
use GoDaddy\WordPress\MWC\Common\Exceptions\WooCommerceCartException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\SanitizationHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\ValidationHelper;
use GoDaddy\WordPress\MWC\Common\Models\Address;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\CartRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\CountriesRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\CouponsRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\ProductsRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\SessionRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Core\Payments\API;
use GoDaddy\WordPress\MWC\Core\Payments\API\Traits\InitializesCartTrait;
use GoDaddy\WordPress\MWC\Core\Payments\API\Traits\VerifiesNonceTrait;
use GoDaddy\WordPress\MWC\Core\Payments\Exceptions\CartValidationException;
use GoDaddy\WordPress\MWC\Core\Payments\Exceptions\InvalidNonceException;
use GoDaddy\WordPress\MWC\Core\Payments\Exceptions\WooCommerceHandlerException;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\ApplePayGateway;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\GooglePayGateway;
use WC_Coupon;
use WC_Discounts;
use WC_Product;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Cart controller.
 */
class CartController extends AbstractController implements ConditionalComponentContract
{
    use InitializesCartTrait;
    use VerifiesNonceTrait;

    /**
     * Sets the endpoint route.
     */
    public function __construct()
    {
        $this->route = 'cart';
    }

    /**
     * Loads the component and registers the endpoint routes.
     */
    public function load()
    {
        $this->registerRoutes();
    }

    /**
     * Registers the endpoint routes.
     */
    public function registerRoutes()
    {
        register_rest_route($this->namespace, '/'.$this->route, [
            [
                'methods'             => 'PATCH',
                'callback'            => [$this, 'updateCart'],
                'permission_callback' => '__return_true',
                'args'                => $this->getUpdateCartArgs(),
                'schema'              => [$this, 'getItemSchema'],
            ],
        ]);
    }

    /**
     * Gets the arguments for the update cart endpoint.
     *
     * @return array
     */
    protected function getUpdateCartArgs() : array
    {
        return [
            'couponCode' => [
                'required' => false,
                'type'     => 'string',
            ],
            'customer' => [
                'required'   => false,
                'type'       => 'object',
                'properties' => [
                    'billingAddress' => [
                        'type'       => 'object',
                        'properties' => [
                            'businessName' => [
                                'type' => 'string',
                            ],
                            'firstName' => [
                                'type' => 'string',
                            ],
                            'lastName' => [
                                'type' => 'string',
                            ],
                            'lines' => [
                                'type'  => 'array',
                                'items' => [
                                    'type' => 'string',
                                ],
                            ],
                            'locality' => [
                                'type' => 'string',
                            ],
                            'subLocalities' => [
                                'type'  => 'array',
                                'items' => [
                                    'type' => 'string',
                                ],
                            ],
                            'administrativeDistricts' => [
                                'type'  => 'array',
                                'items' => [
                                    'type' => 'string',
                                ],
                            ],
                            'postalCode' => [
                                'type' => 'string',
                            ],
                            'countryCode' => [
                                'type' => 'string',
                            ],
                            'phone' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                    'shippingAddress' => [
                        'type'       => 'object',
                        'properties' => [
                            'businessName' => [
                                'type' => 'string',
                            ],
                            'firstName' => [
                                'type' => 'string',
                            ],
                            'lastName' => [
                                'type' => 'string',
                            ],
                            'lines' => [
                                'type'  => 'array',
                                'items' => [
                                    'type' => 'string',
                                ],
                            ],
                            'locality' => [
                                'type' => 'string',
                            ],
                            'subLocalities' => [
                                'type'  => 'array',
                                'items' => [
                                    'type' => 'string',
                                ],
                            ],
                            'administrativeDistricts' => [
                                'type'  => 'array',
                                'items' => [
                                    'type' => 'string',
                                ],
                            ],
                            'postalCode' => [
                                'type' => 'string',
                            ],
                            'countryCode' => [
                                'type' => 'string',
                            ],
                            'phone' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                    'shippingMethod' => [
                        'type' => 'string',
                    ],
                ],
            ],
            'products' => [
                'required' => false,
                'type'     => 'array',
                'items'    => [
                    'type'       => 'object',
                    'properties' => [
                        'attributes' => [
                            'required' => false,
                            'type'     => 'array',
                            'items'    => [
                                'type' => 'string',
                            ],
                        ],
                        'id' => [
                            'required' => true,
                            'type'     => 'integer',
                        ],
                        'quantity' => [
                            'type' => 'float',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Updates the cart with the given data from a request.
     *
     * @internal
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     * @throws Exception
     */
    public function updateCart(WP_REST_Request $request)
    {
        try {
            $this->verifyNonce($request, API::NONCE_ACTION);
            $this->initializeCart();

            $response = [
                'couponCode' => $this->maybeApplyCouponCode($request),
                'customer'   => $this->setCustomerData($request),
                'products'   => $this->addProducts($request),
            ];
        } catch (CartValidationException $exception) {
            $response = new WP_Error($exception->getErrorCode(), $exception->getMessage(), [
                'status' => $exception->getCode(),
                'field'  => $exception->getField(),
            ]);
        } catch (InvalidNonceException $exception) {
            $response = new WP_Error('INVALID_NONCE', $exception->getMessage(), [
                'status' => $exception->getCode(),
            ]);
        }

        return rest_ensure_response($response);
    }

    /**
     * Applies a coupon code to the cart if present in the request and coupons are enabled.
     *
     * @param WP_REST_Request $request
     *
     * @return string|void
     * @throws CartValidationException|WooCommerceCartException
     */
    protected function maybeApplyCouponCode(WP_REST_Request $request)
    {
        // Only skip if the param is not present in the request. If the param is present, but empty/null, we want to remove
        // any existing coupon codes.
        if (! $request->has_param('couponCode')) {
            return;
        }

        if (! CouponsRepository::couponsEnabled()) {
            throw new CartValidationException(__('Coupons are disabled.', 'mwc-core'), 'COUPONS_DISABLED', 'couponCode');
        }

        // if coupon code is non-empty, try to apply it
        if (! empty($couponCode = SanitizationHelper::textField((string) $request->get_param('couponCode')))) {
            return $this->applyCouponCode($couponCode);
        }

        WooCommerceRepository::getCartInstance()->remove_coupons();
    }

    /**
     * Applies the given coupon code to the cart.
     *
     * @param string $couponCode
     *
     * @return string
     * @throws CartValidationException|WooCommerceCartException
     */
    protected function applyCouponCode(string $couponCode) : string
    {
        $coupon = $this->getCoupon($couponCode);

        if ($coupon->get_code() !== $couponCode) {
            throw new CartValidationException(__('Invalid coupon code.', 'mwc-core'), 'INVALID_COUPON_CODE', 'couponCode');
        }

        $this->validateCoupon($coupon);

        if (! WooCommerceRepository::getCartInstance()->add_discount($couponCode)) {
            throw new CartValidationException(__('Coupon cannot be applied.', 'mwc-core'), 'INVALID_COUPON_CODE', 'couponCode');
        }

        return $coupon->get_code();
    }

    /**
     * Validates a given coupon.
     *
     * @param WC_Coupon $coupon
     * @return bool|true
     * @throws CartValidationException|Exception
     */
    protected function validateCoupon(WC_Coupon $coupon) : bool
    {
        $isValid = $this->getDiscountsInstance()->is_coupon_valid($coupon);

        if ($isValid instanceof WP_Error) {
            throw new CartValidationException(sprintf(
                __('Coupon cannot be applied. %s', 'mwc-core'),
                $isValid->get_error_message()
            ), 'INVALID_COUPON_CODE', 'couponCode');
        }

        return true;
    }

    /**
     * Gets the WooCommerce discounts handler instance.
     *
     * @return WC_Discounts
     * @throws Exception
     */
    protected function getDiscountsInstance() : WC_Discounts
    {
        return new WC_Discounts(WooCommerceRepository::getCartInstance());
    }

    /**
     * Gets a WooCommerce coupon instance.
     *
     * @param string $couponCode
     * @return WC_Coupon
     */
    protected function getCoupon(string $couponCode) : WC_Coupon
    {
        return new WC_Coupon($couponCode);
    }

    /**
     * Gets the customer data for the request.
     *
     * @param WP_REST_Request $request
     * @return array|null
     * @throws CartValidationException|WooCommerceHandlerException|Exception
     */
    protected function setCustomerData(WP_REST_Request $request) : ?array
    {
        $customerData = $request->get_param('customer');

        if (empty($customerData)) {
            return null;
        }

        if (! ArrayHelper::accessible($customerData) || ! ArrayHelper::isAssoc($customerData)) {
            throw new CartValidationException(__('Invalid customer data', 'mwc-core'), 'UNKNOWN');
        }

        return [
            'billingAddress'  => $this->setCustomerAddress('billing', $customerData),
            'emailAddress'    => $this->setCustomerEmailAddress($customerData),
            'shippingAddress' => $this->setCustomerAddress('shipping', $customerData),
            'shippingMethod'  => $this->setShippingMethod($customerData),
        ];
    }

    /**
     * Sets the customer email address in session.
     *
     * @param array $customerData
     * @return string|null
     * @throws CartValidationException
     */
    protected function setCustomerEmailAddress(array $customerData) : ?string
    {
        $emailAddress = ArrayHelper::get($customerData, 'emailAddress');
        $emailAddress = is_string($emailAddress) ? SanitizationHelper::textField($emailAddress) : null;
        $isValidEmail = ValidationHelper::isEmail($emailAddress);

        if (null !== $emailAddress && ! $isValidEmail) {
            throw new CartValidationException(__('Invalid email address', 'mwc-core'), 'INVALID_BILLING_CONTACT', 'EMAIL');
        }

        if ($isValidEmail && ($wc = WooCommerceRepository::getInstance()) && is_callable([$wc->customer, 'set_billing_email'])) {
            $wc->customer->set_billing_email($emailAddress);
        }

        return $emailAddress;
    }

    /**
     * Sets the customer address to session.
     *
     * @param string $which either 'billing' or 'shipping'
     * @param array $customerData
     * @return array|null
     * @throws CartValidationException|WooCommerceHandlerException|Exception
     */
    protected function setCustomerAddress(string $which, array $customerData) : ?array
    {
        $addressData = ArrayHelper::get($customerData, "{$which}Address", []);

        if (empty($addressData) || ('billing' !== $which && 'shipping' !== $which)) {
            return null;
        }

        /* translators: Placeholder: %s - either 'billing' or 'shipping' */
        $errorMessage = sprintf(__('Invalid %s address', 'mwc-core'), $which);
        $errorCode = 'INVALID_'.strtoupper($which).'_ADDRESS';

        if (! ArrayHelper::accessible($addressData) || ! ArrayHelper::isAssoc($addressData)) {
            throw new CartValidationException($errorMessage, $errorCode);
        }

        $address = $this->getAdaptedAddress($addressData);
        $validatedAddress = [];

        foreach ($address as $part => $value) {
            $value = is_string($value) ? SanitizationHelper::textField($value) : null;
            $hasError = empty($value);

            // validate mandatory non-empty fields
            switch ($part) {
                case 'city':
                    $errorField = 'LOCALITY';
                    break;
                case 'postcode':
                    $errorField = 'POSTAL_CODE';
                    break;
                case 'country':
                    $errorField = 'COUNTRY_CODE';
                    break;
                default:
                    $errorField = null;
                    $hasError = false;
            }

            if ($hasError && ! empty($errorField)) {
                throw new CartValidationException($errorMessage, $errorCode, $errorField);
            }

            $validatedAddress[$part] = $value;
        }

        if (! $this->validateCountryState($validatedAddress['country'], $validatedAddress['state'])) {
            throw new CartValidationException($errorMessage, $errorCode, 'ADMINISTRATIVE_AREA');
        }

        if ('billing' === $which && $validatedAddress['country'] && ! $this->validateBillingCountry($validatedAddress['country'])) {
            throw new CartValidationException(__("Orders aren't accepted from this location", 'mwc-core'), 'INVALID_BILLING_ADDRESS', 'COUNTRY_CODE');
        }

        if ('shipping' === $which && ! $this->validateShippingCountry($validatedAddress['country'])) {
            throw new CartValidationException($errorMessage, 'UNSERVICEABLE_ADDRESS', 'ADDRESS');
        }

        $wc = WooCommerceRepository::getInstance();

        foreach ($validatedAddress as $part => $value) {
            $method = "set_{$which}_{$part}";

            if ($wc && isset($wc->customer) && is_callable([$wc->customer, $method])) {
                $wc->customer->{$method}($value);
            }
        }

        if ('shipping' === $which && ! $this->hasShippingMethodsAvailable()) {
            throw new CartValidationException($errorMessage, 'UNSERVICEABLE_ADDRESS', 'ADDRESS');
        }

        return $validatedAddress;
    }

    /**
     * Validates a country for billing.
     *
     * @param string $country
     * @return bool
     * @throws Exception
     */
    protected function validateBillingCountry(string $country) : bool
    {
        return ArrayHelper::exists(CountriesRepository::getInstance()->get_allowed_countries(), $country);
    }

    /**
     * Validates a country for shipping.
     *
     * @param string $country
     * @return bool
     * @throws Exception
     */
    protected function validateShippingCountry(string $country) : bool
    {
        return ArrayHelper::exists(CountriesRepository::getInstance()->get_shipping_countries(), $country);
    }

    /**
     * Validates that a state is valid for a given country.
     *
     * @param string $country
     * @param string $state
     * @return bool
     * @throws Exception
     */
    protected function validateCountryState(string $country, string $state) : bool
    {
        $states = CountriesRepository::getInstance()->get_states($country);

        return ('' === $state && empty($states)) || ArrayHelper::exists($states, $state);
    }

    /**
     * Determines whether shipping methods are available for the current cart.
     *
     * @return bool
     * @throws WooCommerceHandlerException
     * @throws Exception
     */
    protected function hasShippingMethodsAvailable() : bool
    {
        if (! $wooCommerceInstance = WooCommerceRepository::getInstance()) {
            throw new WooCommerceHandlerException(__('WooCommerce is not available', 'mwc-core'));
        }

        // The reason to use calculate_shipping instead of get_packages here is to force the cart shipping rates top be updated
        $packages = $wooCommerceInstance->shipping()->calculate_shipping(CartRepository::getInstance()->get_shipping_packages());

        return ! empty(ArrayHelper::get(current($packages), 'rates', []));
    }

    /**
     * Gets an address adapted from the given address data converted to a WooCommerce address.
     *
     * @param array $addressData
     * @return array
     */
    protected function getAdaptedAddress(array $addressData) : array
    {
        return (new AddressAdapter([]))->convertToSource((new Address())->setProperties($addressData));
    }

    /**
     * Sets the shipping method to the current customer session.
     *
     * @param array $customerData
     * @return string|null
     * @throws Exception
     */
    protected function setShippingMethod(array $customerData) : ?string
    {
        $shippingMethod = ArrayHelper::get($customerData, 'shippingMethod');
        $shippingMethod = is_string($shippingMethod) ? SanitizationHelper::textField($shippingMethod) : null;

        if (! empty($shippingMethod)) {
            SessionRepository::getInstance()->set('chosen_shipping_methods', [$shippingMethod]);
        }

        return $shippingMethod;
    }

    /**
     * Gets the product data for the request.
     *
     * @param WP_REST_Request $request
     * @return array
     * @throws Exception|WooCommerceCartException
     */
    protected function addProducts(WP_REST_Request $request) : array
    {
        $products = $request->get_param('products');

        if (empty($products)) {
            return [];
        }

        if (! ArrayHelper::accessible($products)) {
            throw new CartValidationException(__('Invalid products data.', 'mwc-core'), 'UNKNOWN');
        }

        // clear cart before adding new products to cart - essentially replacing any products in cart
        WooCommerceRepository::getCartInstance()->empty_cart();

        $validatedProducts = [];

        foreach ($products as $productData) {
            $product = ProductsRepository::get((int) ArrayHelper::get($productData, 'id'));

            // there is no need to check if the product is purchasable or in stock as the add to cart function from WooCommerce will take care of that, and produce any notice in the front end
            if (! $product) {
                continue;
            }

            if ($addedProductData = $this->addProduct($product, (float) ArrayHelper::get($productData, 'quantity', 1), wc_clean(ArrayHelper::get($productData, 'attributes', [])))) {
                $validatedProducts[] = $addedProductData;
            }
        }

        return $validatedProducts;
    }

    /**
     * Adds a product to the cart.
     *
     * @param WC_Product $product
     * @param float $quantity
     * @param array $attributes
     * @return array|null
     * @throws Exception|WooCommerceCartException
     */
    protected function addProduct(WC_Product $product, float $quantity, array $attributes = []) : ?array
    {
        $cart = WooCommerceRepository::getCartInstance();

        if ($parentId = $product->get_parent_id()) {
            $productId = $parentId;
            $variationId = $addedProductId = $product->get_id();
        } else {
            $productId = $addedProductId = $product->get_id();
            $variationId = 0;
        }

        // WooCommerce will handle any validation about whether the product can actually be added to cart (if it's purchasable, in stock, etc.) and output notices in the front end if not
        $added = $cart->add_to_cart($productId, $quantity, $variationId, $attributes);

        return ! $added ? null : [
            'attributes' => $attributes,
            'id'         => $addedProductId,
            'quantity'   => $quantity,
        ];
    }

    /**
     * Gets the item schema.
     *
     * @internal
     *
     * @return array
     */
    public function getItemSchema() : array
    {
        return [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'status',
            'type'       => 'object',
            'properties' => [
                'couponCode' => [
                    'description' => __('Coupon code to apply to the cart.', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'customer' => [
                    'description' => __('Cart customer data.', 'mwc-core'),
                    'type'        => 'object',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                    'properties'  => [
                        'billingAddress' => [
                            'type'       => 'object',
                            'properties' => [
                                'businessName' => [
                                    'type' => 'string',
                                ],
                                'firstName' => [
                                    'type' => 'string',
                                ],
                                'lastName' => [
                                    'type' => 'string',
                                ],
                                'lines' => [
                                    'type'  => 'array',
                                    'items' => [
                                        'type' => 'string',
                                    ],
                                ],
                                'locality' => [
                                    'type' => 'string',
                                ],
                                'subLocalities' => [
                                    'type'  => 'array',
                                    'items' => [
                                        'type' => 'string',
                                    ],
                                ],
                                'administrativeDistricts' => [
                                    'type'  => 'array',
                                    'items' => [
                                        'type' => 'string',
                                    ],
                                ],
                                'postalCode' => [
                                    'type' => 'string',
                                ],
                                'countryCode' => [
                                    'type' => 'string',
                                ],
                                'phone' => [
                                    'type' => 'string',
                                ],
                            ],
                        ],
                        'emailAddress' => [
                            'type' => 'string',
                        ],
                        'shippingAddress' => [
                            'type'       => 'object',
                            'properties' => [
                                'businessName' => [
                                    'type' => 'string',
                                ],
                                'firstName' => [
                                    'type' => 'string',
                                ],
                                'lastName' => [
                                    'type' => 'string',
                                ],
                                'lines' => [
                                    'type'  => 'array',
                                    'items' => [
                                        'type' => 'string',
                                    ],
                                ],
                                'locality' => [
                                    'type' => 'string',
                                ],
                                'subLocalities' => [
                                    'type'  => 'array',
                                    'items' => [
                                        'type' => 'string',
                                    ],
                                ],
                                'administrativeDistricts' => [
                                    'type'  => 'array',
                                    'items' => [
                                        'type' => 'string',
                                    ],
                                ],
                                'postalCode' => [
                                    'type' => 'string',
                                ],
                                'countryCode' => [
                                    'type' => 'string',
                                ],
                                'phone' => [
                                    'type' => 'string',
                                ],
                            ],
                        ],
                    ],
                ],
                'shippingMethod' => [
                    'type' => 'string',
                ],
                'products' => [
                    'description' => __('Products to add to the cart.', 'mwc-core'),
                    'type'        => 'array',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                    'items'       => [
                        'type'       => 'object',
                        'properties' => [
                            'attributes' => [
                                'type'  => 'array',
                                'items' => [
                                    'type' => 'string',
                                ],
                            ],
                            'id' => [
                                'type' => 'integer',
                            ],
                            'quantity' => [
                                'type' => 'float',
                            ],
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
}
