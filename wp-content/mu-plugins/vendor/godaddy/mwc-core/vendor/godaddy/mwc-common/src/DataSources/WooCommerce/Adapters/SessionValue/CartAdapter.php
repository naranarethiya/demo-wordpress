<?php

namespace GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\SessionValue;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\CouponAdapter;
use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\CurrencyAmountAdapter;
use GoDaddy\WordPress\MWC\Common\Exceptions\AdapterException;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Models\Cart;
use GoDaddy\WordPress\MWC\Common\Models\Coupon;
use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;
use GoDaddy\WordPress\MWC\Common\Models\Orders\FeeItem;
use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Common\Models\Orders\ShippingItem;
use GoDaddy\WordPress\MWC\Common\Models\Orders\TaxItem;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\CouponsRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\ProductsRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use WC_Product;

/**
 * Adapter to convert from the `woocommerce_sessions` `session_value` database column into a native Cart object.
 */
class CartAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var array cart data */
    protected $source;

    /** @var string the cart class name */
    protected $cartClass = Cart::class;

    /**
     * The cart adapter constructor.
     *
     * @param array $data cart data
     */
    public function __construct(array $data)
    {
        $this->source = $data;
    }

    /**
     * Converts cart data from the woocommerce_sessions session_value database column into a native cart object.
     *
     * @return Cart
     * @throws BaseException
     */
    public function convertFromSource() : Cart
    {
        $cart = $this->instantiateCart();

        if (! empty($this->source)) {
            // totals
            $cart->setFeeAmount($this->convertAmountFromSource($this->source, 'cart_totals.fee_total'));
            $cart->setLineAmount($this->convertAmountFromSource($this->source, 'cart_totals.cart_contents_total'));
            $cart->setShippingAmount($this->convertAmountFromSource($this->source, 'cart_totals.shipping_total'));
            $cart->setTaxAmount($this->convertAmountFromSource($this->source, 'cart_totals.total_tax'));
            $cart->setTotalAmount($this->convertAmountFromSource($this->source, 'cart_totals.total'));

            // items
            $cart->setLineItems($this->convertLineItemsFromSource($this->source));
            $cart->setFeeItems($this->convertFeeItemsFromSource($this->source));
            $cart->setShippingItems($this->convertShippingItemsFromSource($this->source));
            $cart->setTaxItems($this->convertTaxItemsFromSource($this->source));

            // other
            $cart->setCoupons($this->convertCouponsFromSource($this->source));
        }

        return $cart;
    }

    /**
     * @note NO-OP
     * @return array{}
     */
    public function convertToSource(?Cart $cart = null) : array
    {
        // no-op, we will never write to the woocommerce_sessions table
        return [];
    }

    /**
     * Constructs a new instance of the configured cart class.
     *
     * @return Cart
     */
    protected function instantiateCart() : Cart
    {
        return new $this->cartClass();
    }

    /**
     * Converts the total amount from the session value.
     *
     * @param array $sessionValue
     * @param string $key
     * @return CurrencyAmount
     */
    protected function convertAmountFromSource(array $sessionValue, string $key) : CurrencyAmount
    {
        return (new CurrencyAmount())
            ->setAmount(ArrayHelper::get($sessionValue, $key, 0) * 100)
            ->setCurrencyCode(WooCommerceRepository::getCurrency());
    }

    /**
     * Converts tax items from source.
     *
     * @param array $sessionValue
     * @return array
     */
    protected function convertTaxItemsFromSource(array $sessionValue) : array
    {
        $taxItems = [];
        $cartItems = ArrayHelper::get($sessionValue, 'cart', []);

        if (! ArrayHelper::accessible($cartItems)) {
            return $taxItems;
        }

        foreach ($cartItems as $cartContent) {
            $taxItems[] = (new TaxItem())->setTotalAmount($this->convertAmountFromSource($cartContent, 'line_tax'));
        }

        return $taxItems;
    }

    /**
     * Converts fee items from source.
     *
     * Note that the default session data does not include individual fee items, only fee totals.
     * Some plugins may or may not record fee item data to the session, but not organized in a predictable way.
     * For convenience and consistency with the fee totals, we will have a singular fee item returned here if the total is > 0.
     *
     * @param array $sessionValue
     * @return FeeItem[]
     */
    protected function convertFeeItemsFromSource(array $sessionValue) : array
    {
        $feeItems = [];
        $feeTotals = $this->convertAmountFromSource($sessionValue, 'cart_totals.fee_total');

        if ($feeTotals->getAmount() > 0) {
            $feeTaxTotal = 0;

            foreach (ArrayHelper::get($sessionValue, 'fee_taxes', []) as $taxAmount) {
                $feeTaxTotal += (float) $taxAmount;
            }

            $feeItems[] = (new FeeItem())
                ->setName('')
                ->setLabel('')
                ->setTotalAmount($feeTotals)
                ->setTaxAmount($this->convertAmountFromSource(['tax' => $feeTaxTotal], 'tax'));
        }

        return $feeItems;
    }

    /**
     * Converts shipping items from source.
     *
     * @param array $sessionValue
     * @return array
     */
    protected function convertShippingItemsFromSource(array $sessionValue) : array
    {
        $shippingItems = [];
        // grabs all `shipping_for_package_<n>` keys where <n> is the package index
        $shippingPackages = ArrayHelper::where($sessionValue, static function ($value, $key) {
            return is_string($key) && StringHelper::startsWith($key, 'shipping_for_package_');
        }, false);

        foreach ($shippingPackages as $shippingPackage) {
            foreach (ArrayHelper::get($shippingPackage, 'rates') as $shippingRate) {
                $shippingItem = $shippingRate ? current((array) $shippingRate) : [];

                $name = ArrayHelper::get($shippingItem, 'method_id', '');
                $label = ArrayHelper::get($shippingItem, 'label', '');
                $cost = ArrayHelper::get($shippingItem, 'cost', 0);

                $shippingCost = new CurrencyAmountAdapter((float) $cost, WooCommerceRepository::getCurrency());
                $shippingItems[] = (new ShippingItem())
                    /* @NOTE cannot set shipping item ID because the WooCommerce shipping ID here is a string, not an integer (see setId method) {unfulvio 2022-02-18} */
                    ->setName($name)
                    ->setLabel($label)
                    ->setTotalAmount($shippingCost->convertFromSource());
            }
        }

        return $shippingItems;
    }

    /**
     * Converts line items from source.
     *
     * @param array $sessionValue
     * @return LineItem[]
     */
    protected function convertLineItemsFromSource(array $sessionValue) : array
    {
        $lineItems = [];
        $cartItems = ArrayHelper::get($sessionValue, 'cart', []);

        if (! ArrayHelper::accessible($cartItems)) {
            return $lineItems;
        }

        foreach ($cartItems as $lineItem) {
            $product = ProductsRepository::get(ArrayHelper::get($lineItem, 'product_id'));

            if (! $product instanceof WC_Product) {
                continue;
            }

            $lineItems[] = (new LineItem())
                ->setQuantity(ArrayHelper::get($lineItem, 'quantity', 1))
                ->setProduct($product)
                ->setVariationId(ArrayHelper::getIntValueForKey($lineItem, 'variation_id'))
                ->setTaxAmount($this->convertAmountFromSource($lineItem, 'line_tax'))
                ->setTotalAmount($this->convertAmountFromSource($lineItem, 'line_total'))
                ->setSubTotalAmount($this->convertAmountFromSource($lineItem, 'line_subtotal'))
                ->setSubTotalTaxAmount($this->convertAmountFromSource($lineItem, 'line_subtotal_tax'));
        }

        return $lineItems;
    }

    /**
     * Converts coupons codes from session data to native coupon objects.
     *
     * @param array $sessionValue
     * @return Coupon[]
     * @throws AdapterException
     */
    protected function convertCouponsFromSource(array $sessionValue) : array
    {
        $coupons = [];

        foreach (ArrayHelper::wrap(ArrayHelper::get($sessionValue, 'applied_coupons', [])) as $couponCode) {
            if (! $coupon = CouponsRepository::get($couponCode)) {
                continue;
            }

            $coupons[] = (new CouponAdapter($coupon))->convertFromSource();
        }

        return $coupons;
    }
}
