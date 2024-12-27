<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers;

use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\AbstractAsset;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;

/**
 * Helper class for parsing assets out of a {@see ProductBase} DTO.
 */
class ProductAssetHelper
{
    /**
     * Gets all assets associated with the product.
     *
     * @param ProductBase $productBase
     * @return AbstractAsset[]
     */
    public static function getAssets(ProductBase $productBase) : array
    {
        return TypeHelper::arrayOf($productBase->assets, AbstractAsset::class, false);
    }

    /**
     * Gets the main asset associated with the product. This is the first asset in the list.
     *
     * @param ProductBase $productBase
     * @return AbstractAsset|null
     */
    public static function getMainAsset(ProductBase $productBase) : ?AbstractAsset
    {
        $assets = static::getAssets($productBase);

        return $assets[0] ?? null;
    }

    /**
     * Gets the "gallery" assets (all except the first).
     *
     * @param ProductBase $productBase
     * @return AbstractAsset[]
     */
    public static function getGalleryAssets(ProductBase $productBase) : array
    {
        $assets = static::getAssets($productBase);

        // remove the first item in the list, as that's the "primary asset"
        array_shift($assets);

        return $assets;
    }
}
