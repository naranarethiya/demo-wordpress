<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services;

use Exception;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Payments\DataStores\WooCommerce\ProductDataStore;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * Service to aid in identifying products that are associated with Poynt.
 *
 * This helps facilitate scenarios like this:
 *  - Product was synced between Woo <=> Poynt previously (so it exists in both places).
 *  - Poynt wrote that product to the platform before Woo did.
 *  - Woo is now reading products from upstream.
 *  - We need to be able to identify if one of those upstream products is a Poynt product that had previously been synced with Woo. (This class aids with this step!)
 */
class PoyntProductAssociationService
{
    /** @var string "type" of external ID used for Poynt products */
    public const POYNT_EXTERNAL_ID_TYPE = 'urn:co.poynt:poynt.product';

    /**
     * Gets the Poynt product ID from the remote resource, if it exists.
     *
     * @param ProductBase $remoteResource
     * @return string|null
     */
    public function getPoyntProductIdFromRemoteResource(ProductBase $remoteResource) : ?string
    {
        if (empty($remoteResource->externalIds)) {
            return null;
        }

        foreach ($remoteResource->externalIds as $externalId) {
            if ($externalId->type === static::POYNT_EXTERNAL_ID_TYPE) {
                return $externalId->value;
            }
        }

        return null;
    }

    /**
     * Gets the {@see Product} from the local Woo database, that matches the Poynt product ID in the provided remote resource object.
     *
     * Ultimately what this is doing is:
     *
     *      1. Ensuring that the supplied ProductBase is identified as being associated with Poynt (must have an `externalId` entry with a Poynt identifier).
     *      2. Querying the local database for a product with meta key = `mwp_poynt_remoteId` and the value matching the Poynt identifier. {@see ProductDataStore::readFromRemoteId()}
     *      3. Returning that {@see Product} object.
     *
     * @param ProductBase $remoteResource
     * @return Product|null
     */
    public function getLocalPoyntProductForRemoteResource(ProductBase $remoteResource) : ?Product
    {
        $poyntProductId = $this->getPoyntProductIdFromRemoteResource($remoteResource);
        if (empty($poyntProductId)) {
            return null;
        }

        try {
            /** @var Product|null $poyntProduct */
            $poyntProduct = CatalogIntegration::withoutReads(fn () => $this->getPoyntDataStore()->readFromRemoteId($poyntProductId));

            return $poyntProduct;
        } catch(Exception $e) {
            return null;
        }
    }

    /**
     * Gets the Poynt product data store.
     *
     * @return ProductDataStore
     */
    protected function getPoyntDataStore() : ProductDataStore
    {
        return new ProductDataStore('poynt');
    }
}
