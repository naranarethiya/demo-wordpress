<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Exceptions\WordPressDatabaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events\AttachmentsInsertedEvent;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Exceptions\AssetUserCreateFailedException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Exceptions\AssetUserNotFoundException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Exceptions\LocalAttachmentCreationFailedException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Factories\CatalogAssetMapRepositoryFactory;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers\AssetComparisonHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers\AssetUserHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\AbstractAsset;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ImageAsset;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Strategies\CatalogAssetUniqueIdentifierStrategy;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\ResourceMapCollection;

/**
 * Service class to insert any missing local attachment records for remote assets.
 */
class AttachmentsService
{
    protected CatalogAssetMapRepositoryFactory $assetMapRepositoryFactory;
    protected CatalogAssetUniqueIdentifierStrategy $catalogAssetUniqueIdentifierStrategy;
    protected AssetComparisonHelper $assetComparisonHelper;
    protected ?int $attachmentAuthorId = null;

    public function __construct(CatalogAssetMapRepositoryFactory $assetMapRepositoryFactory, CatalogAssetUniqueIdentifierStrategy $catalogAssetUniqueIdentifierStrategy, AssetComparisonHelper $assetComparisonHelper)
    {
        $this->assetMapRepositoryFactory = $assetMapRepositoryFactory;
        $this->catalogAssetUniqueIdentifierStrategy = $catalogAssetUniqueIdentifierStrategy;
        $this->assetComparisonHelper = $assetComparisonHelper;
    }

    /**
     * Handles inserting any attachment records that don't exist yet.
     *
     * @param AbstractAsset[] $assets
     * @param int|null $parentId
     * @return int[] IDs of any attachments that were just inserted
     */
    public function handle(array $assets, ?int $parentId = null) : array
    {
        if (empty($assets)) {
            return [];
        }

        $remoteIdentifiers = $this->getRemoteIdentifiersFromAssets($assets);
        $mappings = $this->assetMapRepositoryFactory->getRepository()->getMappingsByRemoteIds($remoteIdentifiers);

        // Determine which assets, if any, exist in the remote array but do not exist in our mapping table.
        $missingRemoteIds = $this->getMissingRemoteIds($remoteIdentifiers, $mappings);

        if (empty($missingRemoteIds)) {
            return [];
        }

        try {
            return $this->findOrCreateMissingAttachments($assets, $missingRemoteIds, $parentId);
        } catch(Exception $e) {
            SentryException::getNewInstance($e->getMessage(), $e);
        }

        return [];
    }

    /**
     * Parses the identifiers out of the supplied assets.
     *
     * @param AbstractAsset[] $assets
     * @return string[]
     */
    protected function getRemoteIdentifiersFromAssets(array $assets) : array
    {
        return array_map([$this->catalogAssetUniqueIdentifierStrategy, 'getIdentifier'], $assets);
    }

    /**
     * Gets the remote IDs of any assets that do not exist in the local database (mapping table).
     * These would be assets that we have to create locally.
     *
     * @param string[] $allRemoteAssetIds Array of all remote asset IDs (whether they exist locally or not)
     * @param ResourceMapCollection $mappings Local mapping records. These are mappings of the asset IDs that _do_ exist
     *                                        in the local database.
     *
     * @return string[]
     */
    protected function getMissingRemoteIds(array $allRemoteAssetIds, ResourceMapCollection $mappings) : array
    {
        /*
         * Because $mappings->getRemoteIds() will be the "database value", we first have to convert $remoteIdentifiers
         * to their database version as well. In the event that the database value is hashed, this ensures we compare
         * a hashed value with another hashed value.
         */
        return array_values(array_diff(array_map([$this->assetMapRepositoryFactory->getRepository(), 'getRemoteIdForDatabase'], $allRemoteAssetIds), $mappings->getRemoteIds()));
    }

    /**
     * Finds associated existing assets that may not be mapped yet, or creates new ones.
     *
     * @param AbstractAsset[] $assets
     * @param string[] $missingRemoteIds
     * @param int|null $parentId
     * @return int[]
     * @throws AssetUserCreateFailedException|AssetUserNotFoundException
     */
    protected function findOrCreateMissingAttachments(array $assets, array $missingRemoteIds, ?int $parentId = null) : array
    {
        // get the actual asset objects to work with
        $unmappedAttachmentAssetDTOs = $this->getUnmappedAttachmentAssetDTOs($assets, $missingRemoteIds);
        if (empty($unmappedAttachmentAssetDTOs)) {
            return [];
        }

        // see if there are any matching attachments already in the local database that haven't been mapped yet
        if ($newlyMappedAttachmentAssetDTOs = $this->findAndAssociateExistingUnmappedAttachments($unmappedAttachmentAssetDTOs)) {
            // exclude the Woo ones from our "missing assets" array -- we don't need to be creating those
            $unmappedAttachmentAssetDTOs = $this->assetComparisonHelper->diffAssets($unmappedAttachmentAssetDTOs, $newlyMappedAttachmentAssetDTOs);
        }

        return $this->createMissingAttachments($unmappedAttachmentAssetDTOs, $parentId);
    }

    /**
     * Creates attachment records that don't exist locally.
     *
     * @param AbstractAsset[] $missingRemoteAssets
     * @return int[] array of new attachment IDs
     * @throws AssetUserCreateFailedException|AssetUserNotFoundException
     */
    protected function createMissingAttachments(array $missingRemoteAssets, ?int $parentId = null) : array
    {
        $attachmentIds = [];

        foreach ($missingRemoteAssets as $missingRemoteAsset) {
            if ($this->shouldInsertAttachment($missingRemoteAsset)) {
                try {
                    $attachmentIds[] = $this->insertAttachment($missingRemoteAsset, $parentId);
                } catch(LocalAttachmentCreationFailedException|WordPressDatabaseException $e) {
                    // report these to sentry, but allow the loop to continue
                    SentryException::getNewInstance($e->getMessage(), $e);
                }
            }
        }

        if ($attachmentIds) {
            Events::broadcast(AttachmentsInsertedEvent::getNewInstance($attachmentIds));
        }

        return $attachmentIds;
    }

    /**
     * Gets an array of {@see AbstractAsset} objects that are missing from our local database.
     *
     * @param AbstractAsset[] $assets array of all assets
     * @param string[] $missingRemoteIds IDs of the assets that are missing
     * @return AbstractAsset[] array of missing assets
     */
    protected function getUnmappedAttachmentAssetDTOs(array $assets, array $missingRemoteIds) : array
    {
        $assetMapRepository = $this->assetMapRepositoryFactory->getRepository();

        return array_filter($assets, function (AbstractAsset $asset) use ($missingRemoteIds, $assetMapRepository) {
            $assetIdentifier = $this->catalogAssetUniqueIdentifierStrategy->getIdentifier($asset);

            // we have to do our comparison using the database values (in the event the ID is hashed, we need to make sure we compare hash with hash, instead of hash with un-hashed!)
            return in_array($assetMapRepository->getRemoteIdForDatabase($assetIdentifier), $missingRemoteIds);
        });
    }

    /**
     * Determines whether we should insert a WordPress attachment record for the supplied asset.
     *
     * At this time we only support image assets and do not insert local records for videos, etc.
     *
     * @param AbstractAsset $asset
     * @return bool
     */
    protected function shouldInsertAttachment(AbstractAsset $asset) : bool
    {
        return $asset instanceof ImageAsset;
    }

    /**
     * Inserts an attachment for the supplied asset.
     *
     * @param AbstractAsset $asset
     * @param int|null $parentId Optional ID of the parent (e.g. product ID).
     * @return int
     * @throws AssetUserCreateFailedException|AssetUserNotFoundException|LocalAttachmentCreationFailedException|WordPressDatabaseException
     */
    protected function insertAttachment(AbstractAsset $asset, ?int $parentId = null) : int
    {
        $attachmentId = wp_insert_attachment(
            [
                'guid'           => $asset->url,
                'post_mime_type' => $asset->contentType ?: 'image/jpeg',
                'post_title'     => $asset->name,
                'post_author'    => $this->getAttachmentAuthorId(),
                'meta_input'     => [
                    '_gd_mwc_is_commerce_asset' => true, // this isn't completely necessary, but it allows us to have an alternative flag for Commerce assets just in case
                ],
            ],
            $this->getAssetFileName($asset),
            $parentId ?: 0,
            true
        );

        if (WordPressRepository::isError($attachmentId)) {
            throw new LocalAttachmentCreationFailedException('Failed to create attachment.');
        }

        $attachmentId = TypeHelper::int($attachmentId, 0);

        $this->addMappingRecord($attachmentId, $asset);

        return $attachmentId;
    }

    /**
     * Gets the file name for the asset.
     *
     * Including this is important so that natural WordPress image detection works. {@see wp_attachment_is_image()}
     *
     * @param AbstractAsset $asset
     * @return string
     */
    protected function getAssetFileName(AbstractAsset $asset) : string
    {
        return parse_url($asset->url, PHP_URL_PATH) ?: $asset->url;
    }

    /**
     * Gets the user ID for attachment records.
     *
     * @return int
     *
     * @throws AssetUserCreateFailedException|AssetUserNotFoundException
     */
    protected function getAttachmentAuthorId() : int
    {
        return $this->attachmentAuthorId ??= AssetUserHelper::getOrCreateUserId();
    }

    /**
     * Inserts a mapping record to link the local attachment to the remote asset.
     *
     * @param int $attachmentId
     * @param AbstractAsset $asset
     * @return void
     * @throws WordPressDatabaseException
     */
    protected function addMappingRecord(int $attachmentId, AbstractAsset $asset) : void
    {
        $this->assetMapRepositoryFactory->getRepository()->add($attachmentId, $this->catalogAssetUniqueIdentifierStrategy->getIdentifier($asset));
    }

    /**
     * Checks all of the supplied assets to see if they were originally uploaded via the local site. If so, we'll try
     * to find an existing attachment record for them.
     *
     * Why we need this: in the past we were writing Woo assets to WooCommerce, but not adding records to our mapping
     * table. Without this check, those assets would be re-inserted as new attachments via {@see static::createMissingAttachments()}.
     * This is a "lazy" way of backfilling those missing mapping records.
     *
     * @param AbstractAsset[] $assets array of assets to check
     * @return AbstractAsset[] array of newly mapped assets that have been found in the local database
     */
    protected function findAndAssociateExistingUnmappedAttachments(array $assets) : array
    {
        $wooAssociatedAssets = [];

        foreach ($assets as $asset) {
            // we check if it's a local asset first, because `findAttachmentIdByUrl()` is not performant and we don't want
            // to run it unnecessarily
            if ($localId = $this->getLocalIdForAsset($asset)) {
                try {
                    $this->addMappingRecord($localId, $asset);
                } catch(Exception $e) {
                }

                $wooAssociatedAssets[] = $asset;
            }
        }

        return $wooAssociatedAssets;
    }

    /**
     * Determines whether an asset was created on the local filesystem.
     *
     * @param AbstractAsset $asset
     * @return bool
     */
    protected function isLocalAsset(AbstractAsset $asset) : bool
    {
        if (! $asset->url) {
            return false;
        }

        $assetDomain = parse_url($asset->url, PHP_URL_HOST);
        $siteUrl = WordPressRepository::getNetworkHomeUrl();

        // it's a local asset if the hosts match
        return $siteUrl && $assetDomain === $siteUrl->getHost();
    }

    /**
     * Finds a local attachment ID for the supplied asset, by searching via URL.
     *
     * This is not performant check, as it compares against `meta_value`. Therefore we only want to run it when we
     * at least know the URL hosts match {@see static::isLocalAsset()}.
     *
     * Once we do find a match, we'll add a mapping record, which means that asset won't be checked again in the future.
     *
     * @param AbstractAsset $asset
     * @return int|null
     */
    protected function findAttachmentIdByUrl(AbstractAsset $asset) : ?int
    {
        return attachment_url_to_postid($asset->url) ?: null;
    }

    /**
     * Gets the local attachment ID for a provided asset, if it's a local asset.
     *
     * @param AbstractAsset $asset
     * @return ?int
     */
    protected function getLocalIdForAsset(AbstractAsset $asset) : ?int
    {
        if ($this->isLocalAsset($asset)) {
            return $this->findAttachmentIdByUrl($asset);
        }

        return null;
    }
}
