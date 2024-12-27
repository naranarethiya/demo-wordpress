<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters;

use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Enums\Products\UpcMetaKeys;
use WC_Product;

/**
 * A class that abstracts the logic necessary to set and get the Global Unique ID
 * from WooCommerce products, keeping all supported meta keys in sync.
 */
class WooCommerceGlobalUniqueIdProvider
{
    use CanGetNewInstanceTrait;

    /**
     * Gets the Global Unique ID from the product metadata.
     *
     * @param WC_Product $wooProduct
     * @return non-empty-string|null
     */
    public function getGlobalUniqueId(WC_Product $wooProduct) : ?string
    {
        foreach (UpcMetaKeys::cases() as $metaKey) {
            switch ($metaKey) {
                case UpcMetaKeys::GlobalUniqueId:
                    $upc = $this->getGlobalUniqueIdProp($wooProduct, $metaKey);
                    break;
                default:
                    $upc = TypeHelper::stringOrNull($wooProduct->get_meta($metaKey));
            }

            if ($upc) {
                return $upc;
            }
        }

        return null;
    }

    /**
     * Gets the value of the Global Unique ID prop of a product using the appropriate method depending on the currently active WooCommerce version.
     *
     * @param WC_Product $wooProduct
     * @param string $metaKey
     * @return string|null
     */
    protected function getGlobalUniqueIdProp(WC_Product $wooProduct, string $metaKey) : ?string
    {
        if ($this->shouldUseGlobalUniqueIdMethods() && method_exists($wooProduct, 'get_global_unique_id')) {
            return TypeHelper::stringOrNull($wooProduct->get_global_unique_id());
        }

        return TypeHelper::stringOrNull($wooProduct->get_meta($metaKey));
    }

    /**
     * Determines whether we are expected to use the {@see WC_Product::get_global_unique_id()} and {@see WC_Product::set_global_unique_id()} methods.
     *
     * We should attempt to use those methods in WooCommerce 9.2.0 or newer.
     */
    protected function shouldUseGlobalUniqueIdMethods() : bool
    {
        return version_compare(WooCommerceRepository::getWooCommerceVersion() ?? '0.0.0', '9.2.0', '>=');
    }

    /**
     * Sets the Global Unique ID as product metadata.
     *
     * @param WC_Product $wooProduct
     * @param non-empty-string $value
     * @return WC_Product
     */
    public function setGlobalUniqueId(WC_Product $wooProduct, string $value) : WC_Product
    {
        foreach (UpcMetaKeys::cases() as $metaKey) {
            switch ($metaKey) {
                case UpcMetaKeys::GlobalUniqueId:
                    $this->setGlobalUniqueIdProp($wooProduct, $metaKey, $value);
                    break;
                default:
                    $wooProduct->update_meta_data($metaKey, $value);
            }
        }

        return $wooProduct;
    }

    /**
     * Sets the Global Unique ID prop on a product using the appropriate method depending on the currently active WooCommerce version.
     *
     * @param WC_Product $wooProduct
     * @param string $metaKey
     * @param string $value
     * @return void
     */
    protected function setGlobalUniqueIdProp(WC_Product $wooProduct, string $metaKey, string $value) : void
    {
        if ($this->shouldUseGlobalUniqueIdMethods() && method_exists($wooProduct, 'set_global_unique_id')) {
            $wooProduct->set_global_unique_id($value);
        } else {
            $wooProduct->update_meta_data($metaKey, $value);
        }
    }
}
