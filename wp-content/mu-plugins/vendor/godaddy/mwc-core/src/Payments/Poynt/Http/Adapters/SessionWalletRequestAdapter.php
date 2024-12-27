<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\CountriesRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\CouponsRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\SessionRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\SiteRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Payments\Exceptions\WooCommerceCartException;
use GoDaddy\WordPress\MWC\Core\Payments\Exceptions\WooCommerceHandlerException;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\WalletRequestCouponCodeObject;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\WalletRequestLineItemObject;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\WalletRequestObject;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\WalletRequestShippingMethodObject;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\WalletRequestTotalObject;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\ApplePayGateway;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\GooglePayGateway;
use WC_Cart;
use WC_Product;
use WC_Shipping_Rate;

/**
 * The session wallet request adapter, used to work with WalletRequestObject instances.
 */
class SessionWalletRequestAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var WC_Cart */
    protected $source;

    /**
     * SessionWalletRequestAdapter constructor.
     *
     * @param WC_Cart $cart
     */
    public function __construct(WC_Cart $cart)
    {
        $this->source = $cart;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function convertFromSource() : WalletRequestObject
    {
        $walletRequestObject = new WalletRequestObject();

        // Repository properties
        $walletRequestObject->setCountry(WooCommerceRepository::getBaseCountry());
        $walletRequestObject->setCurrency(WooCommerceRepository::getCurrency());
        $walletRequestObject->setMerchantName(SiteRepository::getTitle());
        $walletRequestObject->setSupportCouponCode(CouponsRepository::couponsEnabled());

        // Shipping properties
        if ($this->needsShipping()) {
            $walletRequestObject
                ->setRequireShippingAddress(true)
                ->setShippingType($this->getShippingType())
                ->setShippingMethods($this->getShippingMethods());
        }

        // Line items
        $walletRequestObject
            ->setLineItems($this->getLineItems())
            ->setTotal((new WalletRequestTotalObject())
                ->setAmount($this->getTotal())
                ->setLabel(SiteRepository::getTitle())
            );

        // Fields
        $walletRequestObject
            ->setRequireEmail($this->isAddressFieldRequired('email'))
            ->setRequirePhone($this->isAddressFieldRequired('phone'));

        // Coupons
        if ($couponCode = $this->getAppliedCouponCode()) {
            $walletRequestObject->setCouponCode((new WalletRequestCouponCodeObject())
                ->setCode($couponCode)
                ->setLabel(__('Coupon code', 'mwc-core'))
            );
        }

        // Wallets
        $walletRequestObject
            ->setDisableWallets([
                'applePay'  => ! ApplePayGateway::isActive(),
                'googlePay' => ! GooglePayGateway::isActive(),
            ]);

        return $walletRequestObject;
    }

    /**
     * {@inheritDoc}
     *
     * @return array<array<string, WC_Product|float>>
     */
    public function convertToSource() : array
    {
        // Not implemented.
        return [];
    }

    /**
     * Gets the coupon code that may already be applied to the cart.
     *
     * Wallet payments only supports a single coupon, so if multiple are applied we return nothing. This value is only used for
     * display, so in that scenario the proper discounts will still be reflected in the totals, and the coupon field
     * will be available to apply additional coupons.
     *
     * @TODO: Update this method if Poynt accepts multiple coupon codes in MWC-8713 {acastro1 2022-10-17}
     *
     * @return string
     */
    protected function getAppliedCouponCode() : string
    {
        $couponCodes = $this->source->get_applied_coupons();

        if (! ArrayHelper::accessible($couponCodes) || 1 !== count($couponCodes)) {
            return '';
        }

        return (string) current($couponCodes);
    }

    /**
     * Gets the items from the cart.
     *
     * @return array<mixed> the session's cart items
     */
    protected function getCartItems() : array
    {
        return ArrayHelper::wrap($this->source->get_cart());
    }

    /**
     * Gets the chosen shipping methods.
     *
     * @return string[] list of chosen shipping methods
     * @throws Exception
     */
    protected function getChosenShippingMethods() : array
    {
        return ArrayHelper::wrap(SessionRepository::get('chosen_shipping_methods', []));
    }

    /**
     * Gets the line items, formatted for this payment request.
     *
     * @return WalletRequestLineItemObject[]
     */
    protected function getLineItems() : array
    {
        $cart = $this->source;
        $result = [];

        if ($subTotal = $cart->get_subtotal()) {
            $result[] = WalletRequestLineItemObject::getNewInstance()
                ->setAmount(round($subTotal, 2))
                ->setLabel(__('Subtotal', 'mwc-core'));
        }

        if ($discountTotal = $cart->get_discount_total()) {
            $result[] = WalletRequestLineItemObject::getNewInstance()
                ->setAmount(-round($discountTotal, 2))
                ->setLabel(__('Discount', 'mwc-core'));
        }

        if ($shippingTotal = $cart->get_shipping_total()) {
            $result[] = WalletRequestLineItemObject::getNewInstance()
                ->setAmount(round($shippingTotal, 2))
                ->setLabel(__('Shipping', 'mwc-core'));
        }

        if (! empty($fees = $cart->get_fees())) {
            foreach ($fees as $fee) {
                $result[] = WalletRequestLineItemObject::getNewInstance()
                    ->setAmount(round($fee->amount, 2))
                    ->setLabel($fee->name);
            }
        }

        if ($taxTotal = $cart->get_taxes_total(false, false)) {
            $result[] = WalletRequestLineItemObject::getNewInstance()
                ->setAmount(round($taxTotal, 2))
                ->setLabel(__('Tax', 'mwc-core'));
        }

        return $result;
    }

    /**
     * Gets the required fields, given the type.
     *
     * @param array<string, array<string, mixed>> $addressFields list of associative arrays containing a required field, keyed by the field type
     * @param string $addressType the field type - "shipping" or "billing"
     * @return array<string> list of required field identifiers (email, phone, etc)
     */
    protected function getRequiredAddressFields(array $addressFields, string $addressType) : array
    {
        $requiredFields = [];

        foreach ($addressFields as $key => $field) {
            if (isset($field['required']) && $field['required']) {
                $requiredFields[] = str_replace($addressType.'_', '', $key);
            }
        }

        return array_unique($requiredFields);
    }

    /**
     * Gets the available shipping methods.
     *
     * @return WalletRequestShippingMethodObject[]
     *
     * @throws WooCommerceHandlerException|WooCommerceCartException
     * @throws Exception
     */
    protected function getShippingMethods() : array
    {
        $methods = array_values(array_map(static function (WC_Shipping_Rate $rate) {
            return WalletRequestShippingMethodObject::getNewInstance()
                ->setId($rate->get_id())
                ->setLabel($rate->get_label())
                ->setAmount(round((float) $rate->get_cost(), 2));
        }, $this->getShippingPackageRates()));

        if ($chosenShippingMethod = ArrayHelper::get($this->getChosenShippingMethods(), '0')) {
            usort($methods, static function (WalletRequestShippingMethodObject $method) use ($chosenShippingMethod) {
                return $chosenShippingMethod === $method->getId() ? -1 : 1;
            });
        }

        return $methods;
    }

    /**
     * Gets the current shipping package rates.
     *
     * @return WC_Shipping_Rate[]
     * @throws WooCommerceCartException|WooCommerceHandlerException
     */
    protected function getShippingPackageRates() : array
    {
        if (! $wooCommerceInstance = WooCommerceRepository::getInstance()) {
            throw new WooCommerceHandlerException(__('WooCommerce is not available', 'mwc-core'));
        }

        $packages = $wooCommerceInstance->shipping()->get_packages();

        if (count($packages) > 1) {
            throw new WooCommerceCartException(__('Wallet payments cannot be used for carts with multiple shipments.', 'mwc-core'));
        }

        return ArrayHelper::get(current($packages), 'rates', []);
    }

    /**
     * Gets the shipping type for an order.
     *
     * @return string|null
     * @throws Exception
     */
    protected function getShippingType() : ?string
    {
        $shippingMethods = $this->getChosenShippingMethods();

        if (empty($shippingMethods) || count($shippingMethods) > 1) {
            return null;
        }

        switch (StringHelper::before(current($shippingMethods), ':')) {
            case 'local_pickup':
            case 'local_pickup_plus':
                return 'pickup';

            case 'mwc_local_delivery':
                return 'delivery';

            default:
                return 'shipping';
        }
    }

    /**
     * Gets the cart total.
     *
     * @return float the total price in the cart
     */
    protected function getTotal() : float
    {
        return round(TypeHelper::float(ArrayHelper::get($this->source->get_totals(), 'total'), 0), 2);
    }

    /**
     * Determines if the given field is required either for billing or shipping.
     *
     * @param string $field
     * @return bool
     * @throws BaseException
     * @throws Exception
     */
    protected function isAddressFieldRequired(string $field) : bool
    {
        return ArrayHelper::contains(
            ArrayHelper::combine(
                $this->getRequiredAddressFields(CountriesRepository::getInstance()->get_address_fields('', 'billing_'), 'billing'),
                $this->getRequiredAddressFields(CountriesRepository::getInstance()->get_address_fields('', 'shipping_'), 'shipping')
            ),
            $field
        );
    }

    /**
     * Determines whether the payment request requires shipping.
     *
     * @return bool
     */
    protected function needsShipping() : bool
    {
        return $this->source->needs_shipping_address();
    }
}
