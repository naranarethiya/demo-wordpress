<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Strategies;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\AbstractAsset;

/**
 * Strategy for determining the unique identifier of a remote asset.
 *
 * Right now assets don't have their own UUIDs, but they will in the future. For now we use the URL as the unique
 * identifier, but this will eventually change.
 */
class CatalogAssetUniqueIdentifierStrategy
{
    /**
     * Gets the un-modified identifier from the asset.
     *
     * @param AbstractAsset $asset
     * @return string
     */
    public function getIdentifier(AbstractAsset $asset) : string
    {
        if ($primaryIdentifier = $this->getPrimaryIdentifier($asset)) {
            return $primaryIdentifier;
        }

        return $this->getSecondaryIdentifier($asset);
    }

    /**
     * Gets the primary identifier.
     *
     * This identifier does not exist yet. In the future it will be a UUID, once that's added to the API.
     *
     * @param AbstractAsset $asset
     * @return string|null
     */
    protected function getPrimaryIdentifier(AbstractAsset $asset) : ?string
    {
        return null;
    }

    /**
     * Gets the secondary identifier.
     *
     * This is the asset URL, as that's the most unique piece of information we have for now.
     *
     * @param AbstractAsset $asset
     * @return string
     */
    protected function getSecondaryIdentifier(AbstractAsset $asset) : string
    {
        return $asset->url;
    }
}
