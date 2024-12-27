<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Enums\Products;

use GoDaddy\WordPress\MWC\Common\Traits\EnumTrait;

/**
 * Enum-like class containing a list of meta keys where the product UPC value might be stored.
 */
class UpcMetaKeys
{
    use EnumTrait;

    /** @var string WooCommerce core meta key in Woo 9.2+ */
    public const GlobalUniqueId = '_global_unique_id';

    /** @var string meta key created by Managed WooCommerce */
    public const Mwc = '_mwc_product_upc';

    /** @var string key used by third party plugin https://wordpress.org/plugins/ean-for-woocommerce */
    public const EanForWoo = '_alg_ean';
}
