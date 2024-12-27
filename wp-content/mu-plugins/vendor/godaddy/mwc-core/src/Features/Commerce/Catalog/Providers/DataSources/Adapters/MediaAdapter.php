<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Models\Image;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Factories\CatalogAssetMapRepositoryFactory;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers\ProductAssetHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\AbstractAsset;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ImageAsset;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\VideoAsset;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Strategies\CatalogAssetUniqueIdentifierStrategy;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * Adapter for converting a {@see Product} media assets into {@see AbstractAsset} objects.
 *
 * @see ImageAsset
 * @see VideoAsset
 *
 * @method static static getNewInstance(CatalogAssetMapRepositoryFactory $assetMapRepository, CatalogAssetUniqueIdentifierStrategy $catalogAssetUniqueIdentifierStrategy)
 */
class MediaAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    protected CatalogAssetMapRepositoryFactory $assetMapRepositoryFactory;
    protected CatalogAssetUniqueIdentifierStrategy $catalogAssetUniqueIdentifierStrategy;

    public function __construct(CatalogAssetMapRepositoryFactory $assetMapRepository, CatalogAssetUniqueIdentifierStrategy $catalogAssetUniqueIdentifierStrategy)
    {
        $this->assetMapRepositoryFactory = $assetMapRepository;
        $this->catalogAssetUniqueIdentifierStrategy = $catalogAssetUniqueIdentifierStrategy;
    }

    /**
     * Converts a {@see Product} media assets into an array of {@see AbstractAsset} objects.
     *
     * @param Product|null $product Local product object.
     * @param ProductBase|null $remoteProduct Optional: remote product object from the platform. This can be supplied
     *                                        in case we need to merge any local and remote data together.
     * @return AbstractAsset[]|ImageAsset[]|VideoAsset[]
     */
    public function convertToSource(Product $product = null, ?ProductBase $remoteProduct = null) : array
    {
        $assets = [];

        if ($product) {
            // add the main image ahead of the others from the gallery
            if ($imageAsset = $this->convertImageToSource($product->getMainImage())) {
                $assets[] = $imageAsset;
            }

            foreach ($product->getImages() as $galleryImage) {
                if ($imageAsset = $this->convertImageToSource($galleryImage)) {
                    $assets[] = $imageAsset;
                }
            }
        }

        /*
         * WooCommerce only supports image assets, but the remote platform supports other types.
         * To ensure we don't lose any remote data, we merge in any non-image assets from the remote platform back
         * into this array.
         */
        if ($remoteProduct && $remoteProduct->assets) {
            $assets = $this->maybeInjectUnsupportedRemoteAssets($assets, $remoteProduct->assets);
        }

        return $assets;
    }

    /**
     * Injects any unsupported remote assets into the array of locally-supported assets.
     *
     * @param ImageAsset[] $localAssets Locally-supported assets (for now, images only) that have been converted into DTOs.
     * @param AbstractAsset[] $remoteAssets Original, unmodified assets from the remote platform.
     * @return AbstractAsset[]
     */
    protected function maybeInjectUnsupportedRemoteAssets(array $localAssets, array $remoteAssets) : array
    {
        if ($nonImageAssets = $this->getNonImageAssets($remoteAssets)) {
            try {
                /** @var AbstractAsset[] $localAssets */
                $localAssets = ArrayHelper::combine($localAssets, $nonImageAssets);
            } catch(Exception $e) {
            }
        }

        return $localAssets;
    }

    /**
     * @param AbstractAsset[] $assets
     * @return AbstractAsset[]
     */
    protected function getNonImageAssets(array $assets) : array
    {
        return ArrayHelper::where($assets, fn (AbstractAsset $asset) => ! $asset instanceof ImageAsset, false);
    }

    /**
     * Converts an {@see Image} into an {@see ImageAsset}.
     *
     * @param Image|null $image
     * @return ImageAsset|null
     */
    protected function convertImageToSource(?Image $image) : ?ImageAsset
    {
        if (! $image) {
            return null;
        }

        try {
            return ImageAsset::getNewInstance([
                'contentType' => $image->getMimeType(),
                'name'        => $image->getLabel(),
                'url'         => $image->getSize('full')->getUrl(),
                'thumbnail'   => $image->getSize('thumbnail')->getUrl(),
            ]);
        } catch (Exception $exception) {
            new SentryException($exception);
        }

        return null;
    }

    /**
     * @inerhitDoc
     */
    public function convertFromSource()
    {
        // no-op
    }

    /**
     * Converts the primary asset from its source to a local attachment ID.
     *
     * @param ProductBase $productBase
     * @return int|null
     */
    public function convertPrimaryAssetFromSource(ProductBase $productBase) : ?int
    {
        $mainAsset = ProductAssetHelper::getMainAsset($productBase);

        if (! $mainAsset) {
            return null;
        }

        return $this->assetMapRepositoryFactory->getRepository()->getLocalId($this->catalogAssetUniqueIdentifierStrategy->getIdentifier($mainAsset));
    }

    /**
     * Converts gallery assets from source to an array of local attachment IDs.
     *
     * @param ProductBase $productBase
     * @return int[]
     */
    public function convertGalleryAssetsFromSource(ProductBase $productBase) : array
    {
        $assets = ProductAssetHelper::getGalleryAssets($productBase);

        if (empty($assets)) {
            return [];
        }

        $remoteIdentifiers = array_map([$this->catalogAssetUniqueIdentifierStrategy, 'getIdentifier'], $assets);

        return $this->assetMapRepositoryFactory->getRepository()->getMappingsByRemoteIds($remoteIdentifiers)->getLocalIds();
    }
}
