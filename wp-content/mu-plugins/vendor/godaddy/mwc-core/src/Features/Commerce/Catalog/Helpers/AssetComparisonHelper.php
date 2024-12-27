<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\AbstractAsset;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Strategies\CatalogAssetUniqueIdentifierStrategy;

/**
 * Helper class to compare {@see AbstractAsset} instances.
 */
class AssetComparisonHelper
{
    protected CatalogAssetUniqueIdentifierStrategy $catalogAssetUniqueIdentifierStrategy;

    public function __construct(CatalogAssetUniqueIdentifierStrategy $catalogAssetUniqueIdentifierStrategy)
    {
        $this->catalogAssetUniqueIdentifierStrategy = $catalogAssetUniqueIdentifierStrategy;
    }

    /**
     * Computes the difference of the provided array of assets.
     *
     * @param AbstractAsset[] $mainAssets main array of assets to check
     * @param AbstractAsset[] $comparisonAssets array to compare with
     * @return AbstractAsset[] all the assets in `$mainAssets` that are not present in `$comparisonAssets`
     */
    public function diffAssets(array $mainAssets, array $comparisonAssets) : array
    {
        return array_values(array_udiff($mainAssets, $comparisonAssets, [$this, 'compareAssets']));
    }

    /**
     * Compares two assets to see if they represent the same asset.
     *
     * @param AbstractAsset $asset1
     * @param AbstractAsset $asset2
     * @return int<-1, 1> 0 if the assets are the same, -1 if not
     */
    public function compareAssets(AbstractAsset $asset1, AbstractAsset $asset2) : int
    {
        if ($this->catalogAssetUniqueIdentifierStrategy->getIdentifier($asset1) === $this->catalogAssetUniqueIdentifierStrategy->getIdentifier($asset2)) {
            return 0;
        }

        return -1;
    }
}
