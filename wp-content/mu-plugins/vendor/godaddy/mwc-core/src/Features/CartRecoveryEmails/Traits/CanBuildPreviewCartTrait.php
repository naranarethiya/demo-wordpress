<?php

namespace GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Traits;

use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\Cart;
use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;
use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use WC_Product;

trait CanBuildPreviewCartTrait
{
    /**
     * Gets a fake cart to generate preview data.
     *
     * @return Cart
     */
    protected function getPreviewCart() : Cart
    {
        $realProducts = TypeHelper::arrayOf(
            wc_get_products([
                'orderby' => 'date',
                'order'   => 'DESC',
                'limit'   => 2,
            ]),
            WC_Product::class,
            false
        );

        $lineItems = [];
        $cartTotal = 0;

        foreach ($realProducts as $product) {
            $productPrice = TypeHelper::float($product->get_price('edit'), 0.0);

            $lineItems[] = (new LineItem())
                ->setProduct($product)
                ->setQuantity(2)
                ->setSubTotalAmount(
                    (new CurrencyAmount())
                        // the price of two products in cents
                        ->setAmount((int) ($productPrice * 200))
                        ->setCurrencyCode(get_woocommerce_currency())
                )
                ->setTaxAmount(
                    (new CurrencyAmount())
                        // 7% tax applied to the price of two products in cent -- hardcoded to 7% just so we have
                        // something to show if there are no taxes
                        ->setAmount((int) ($productPrice * 14))
                        ->setCurrencyCode(get_woocommerce_currency())
                )
                ->setTotalAmount(
                    (new CurrencyAmount())
                        // total cost of two products including 7% tax
                        ->setAmount((int) ($productPrice * 214))
                        ->setCurrencyCode(get_woocommerce_currency())
                );

            $cartTotal += (int) ($productPrice * 214);
        }

        return (new Cart())
            ->setTotalAmount(
                (new CurrencyAmount())
                    ->setAmount($cartTotal)
                    ->setCurrencyCode(get_woocommerce_currency()))
            ->setLineItems($lineItems);
    }
}
