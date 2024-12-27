<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Models\AbstractAttachment;
use GoDaddy\WordPress\MWC\Common\Models\Exceptions\ImageSizeNotFound;
use GoDaddy\WordPress\MWC\Common\Models\Image;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Factories\CatalogAssetMapRepositoryFactory;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\AbstractAsset;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Assets\AssetAssociation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ImageAsset;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Strategies\CatalogAssetUniqueIdentifierStrategy;

/**
 * Helper class to map local assets to their remote counterparts.
 *
 * @NOTE This class will likely no longer be needed once we have dedicated asset endpoints and can perform more normal
 * asset CRUD operations.
 */
class MapAssetsHelper
{
    protected CatalogAssetMapRepositoryFactory $catalogAssetMapRepositoryFactory;
    protected CatalogAssetUniqueIdentifierStrategy $catalogAssetUniqueIdentifierStrategy;

    public function __construct(CatalogAssetMapRepositoryFactory $catalogAssetMapRepositoryFactory, CatalogAssetUniqueIdentifierStrategy $catalogAssetUniqueIdentifierStrategy)
    {
        $this->catalogAssetMapRepositoryFactory = $catalogAssetMapRepositoryFactory;
        $this->catalogAssetUniqueIdentifierStrategy = $catalogAssetUniqueIdentifierStrategy;
    }

    /**
     * Inserts mapping records to link the supplied local images with their remote counterparts.
     *
     * @param AbstractAttachment[] $localAttachments
     * @param AbstractAsset[] $remoteAssets
     * @return void
     */
    public function addMappings(array $localAttachments, array $remoteAssets) : void
    {
        $associations = $this->buildAssetAssociations($localAttachments, $remoteAssets);

        if ($associations) {
            $this->addOrUpdateMappingRecords($associations);
        }
    }

    /**
     * Builds associations between the supplied local images and their remote counterparts.
     *
     * @param AbstractAttachment[] $localAttachments
     * @param AbstractAsset[] $remoteAssets
     * @return AssetAssociation[]
     */
    protected function buildAssetAssociations(array $localAttachments, array $remoteAssets) : array
    {
        $assetAssociations = [];

        foreach ($localAttachments as $localImage) {
            if ($localImage instanceof Image && $remoteAsset = $this->findCorrespondingRemoteImageAsset($localImage, $remoteAssets)) {
                $assetAssociations[] = AssetAssociation::getNewInstance([
                    'remoteResource' => $remoteAsset,
                    'localId'        => $localImage->getId(),
                ]);
            }
        }

        return $assetAssociations;
    }

    /**
     * Finds the {@see ImageAsset} that corresponds to the supplied {@see Image}.
     *
     * This does a match based on the URL.
     *
     * @NOTE At this time we only support reading image assets into WooCommerce. If/when we support other asset types
     * this method may need to be adjusted to account for that.
     *
     * @param Image $localImage
     * @param AbstractAsset[] $remoteAssets
     * @return ImageAsset|null
     */
    protected function findCorrespondingRemoteImageAsset(Image $localImage, array $remoteAssets) : ?ImageAsset
    {
        // find results where the remote URL matches the local URL
        /** @var ImageAsset[] $results */
        $results = ArrayHelper::where(
            $remoteAssets,
            function (AbstractAsset $asset) use ($localImage) {
                return $this->hasImageAsset($asset, $localImage);
            },
            false
        );

        return $results[0] ?? null;
    }

    /**
     * Updates the mapping database for the supplied associations.
     *
     * Any missing records are added, and existing ones are updated.
     *
     * @param AssetAssociation[] $assetAssociations
     * @return void
     */
    protected function addOrUpdateMappingRecords(array $assetAssociations) : void
    {
        $repository = $this->catalogAssetMapRepositoryFactory->getRepository();

        foreach ($assetAssociations as $assetAssociation) {
            try {
                $repository->addOrUpdateRemoteId($assetAssociation->localId, $this->catalogAssetUniqueIdentifierStrategy->getIdentifier($assetAssociation->remoteResource));
            } catch(Exception $e) {
            }
        }
    }

    /**
     * @param AbstractAsset $asset
     * @param Image $localImage
     * @return bool
     */
    protected function hasImageAsset(AbstractAsset $asset, Image $localImage) : bool
    {
        try {
            return $asset instanceof ImageAsset && $asset->url === $localImage->getSize('full')->getUrl();
        } catch (ImageSizeNotFound $e) {
            return false;
        }
    }
}
