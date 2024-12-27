<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\ListProductsOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\ListProductsOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductAssociation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\Contracts\ListProductsResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingRemoteIdsAfterLocalIdConversionException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\ProductMapRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Exceptions\CachingStrategyException;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * Helper for ensuring that all the local variants match up with what's listed in the platform, and handling any necessary deletions.
 * For example: if a product has "Variant A" and "Variant B", but "B" gets deleted remotely, then we need to ensure that we also delete that "B" variant in the local database.
 */
class VariantUpdateDeleteHelper
{
    /** @var ProductsMappingServiceContract */
    protected ProductsMappingServiceContract $productsMappingService;

    /** @var ProductsServiceContract */
    protected ProductsServiceContract $productsService;

    protected RemoteProductNotFoundHelper $remoteProductNotFoundHelper;

    protected ProductMapRepository $productMapRepository;

    /**
     * Constructor.
     *
     * @param ProductsMappingServiceContract $productsMappingService
     * @param ProductsServiceContract $productsService
     * @param RemoteProductNotFoundHelper $remoteProductNotFoundHelper
     * @param ProductMapRepository $productMapRepository
     */
    public function __construct(
        ProductsMappingServiceContract $productsMappingService,
        ProductsServiceContract $productsService,
        RemoteProductNotFoundHelper $remoteProductNotFoundHelper,
        ProductMapRepository $productMapRepository
    ) {
        $this->productsMappingService = $productsMappingService;
        $this->productsService = $productsService;
        $this->remoteProductNotFoundHelper = $remoteProductNotFoundHelper;
        $this->productMapRepository = $productMapRepository;
    }

    /**
     * Updates and/or deletes local variations of a given product by its post ID.
     *
     * @param int $localId
     * @return void
     * @throws Exception|CommerceExceptionContract
     */
    public function reconcileVariantsForProductByPostId(int $localId) : void
    {
        if ($remoteId = $this->getRemoteIdForLocalId($localId)) {
            $variations = $this->getAndUpdateVariantsForRemoteProduct($remoteId)->getProducts();
            $this->deleteRemotelyDeletedLocalVariations($localId, $variations);
        }
    }

    /**
     * Deletes local variations where the corresponding remote resource has been deleted.
     *
     * @param int $parentLocalId local ID of the parent product we're handling variations for
     * @param ProductAssociation[] $variations variations retrieved from the API
     * @return void
     */
    protected function deleteRemotelyDeletedLocalVariations(int $parentLocalId, array $variations) : void
    {
        $variantLocalIds = $this->getVariantLocalIdsByParentId($parentLocalId);

        // if we still don't have any, then there's nothing to delete
        if (empty($variantLocalIds)) {
            return;
        }

        $variantIdsToDelete = $this->determineDeletedRemoteVariants($variantLocalIds, $variations);

        foreach ($variantIdsToDelete as $localVariantId) {
            $this->remoteProductNotFoundHelper->handle($localVariantId);
        }
    }

    /**
     * Maybe gets variant local IDs by a given parent product ID.
     *
     * @param int $parentId
     * @return int[]
     */
    protected function getVariantLocalIdsByParentId(int $parentId) : array
    {
        // attempt to get the variant local IDs from the transient first
        // if we can get this here the it saves us from having to do a slightly less efficient DB query below
        $variantLocalIds = $this->maybeGetVariantLocalIdsByParentFromTransient($parentId);

        // if we didn't get any from the transient, then we'll have to query the database
        if (empty($variantLocalIds)) {
            $variantLocalIds = $this->queryForLocalVariantIdsByParentId($parentId);
        }

        return $variantLocalIds;
    }

    /**
     * Reference WooCommerce's `wc_product_children_<product_id>` transient to retrieve the product's children.
     *
     * @param int $parentId
     * @return int[]
     */
    protected function maybeGetVariantLocalIdsByParentFromTransient(int $parentId) : array
    {
        $children_transient_name = 'wc_product_children_'.$parentId;
        $children = get_transient($children_transient_name);

        return TypeHelper::arrayOfIntegers(ArrayHelper::getArrayValueForKey(ArrayHelper::wrap($children), 'all'), false);
    }

    /**
     * Gets the local IDs of all variants for the supplied parent.
     *
     * @param int $parentLocalId
     * @return int[]
     */
    protected function queryForLocalVariantIdsByParentId(int $parentLocalId) : array
    {
        $localVariantIds = CatalogIntegration::withoutReads(function () use ($parentLocalId) {
            return get_posts([
                'fields'                 => 'ids',
                'nopaging'               => true,
                'post_parent'            => $parentLocalId,
                'post_status'            => 'any',
                'post_type'              => CatalogIntegration::PRODUCT_VARIATION_POST_TYPE,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
            ]);
        });

        return TypeHelper::arrayOfIntegers($localVariantIds, false);
    }

    /**
     * Determines variants that have been deleted in the remote platform.
     *
     * We figure this out by comparing what the local site believes are the variants according to DB records, with the
     * IDs we got from the API. If we have any IDs in our local array that do not exist in the remote one, then that
     * tells us they've been deleted upstream.
     *
     * @param int[] $variantLocalIds IDs of local variants -- what Woo believes to be the variants as per the DB
     * @param ProductAssociation[] $remoteVariations remote variants retrieved from the API -- source of truth
     * @return int[] array of local Woo product IDs to delete
     */
    protected function determineDeletedRemoteVariants(array $variantLocalIds, array $remoteVariations) : array
    {
        // get the mapping records for those local IDs so we can compare with what we got from the API
        $mapCollection = $this->productMapRepository->getMappingsByLocalIds($variantLocalIds);

        // gather up all the UUIDs we got from the API -- this is the source of truth
        $remoteVariationUuids = array_map(fn (ProductAssociation $productAssociation) => $productAssociation->remoteResource->productId, $remoteVariations);

        /*
         * Any UUIDs that exist in our local collection but NOT in the remote array have presumably been deleted
         * upstream. These are the ones we'll want to delete from the local database.
         */
        $deletedUuids = array_diff($mapCollection->getRemoteIds(), $remoteVariationUuids);

        // converted the deleted UUIDs into their local ID equivalent
        $localIdsToDelete = array_map(fn (string $deletedUuid) => $mapCollection->getLocalId($deletedUuid), $deletedUuids);

        return array_values(array_filter($localIdsToDelete));
    }

    /**
     * Gets (and also locally updates) local variations of a parent product (by remote IDs).
     *
     * This will insert missing variations {@see AbstractResourceAssociationBuilder::getRemoteResourceLocalId()}
     * which gets called by {@see AbstractListRemoteResourcesService::list()}
     * when calling {@see ProductsService::listProducts()} below.
     *
     * @param string $remoteId
     * @return ListProductsResponseContract
     * @throws CommerceExceptionContract|CachingStrategyException|BaseException|MissingRemoteIdsAfterLocalIdConversionException
     */
    protected function getAndUpdateVariantsForRemoteProduct(string $remoteId) : ListProductsResponseContract
    {
        return $this->productsService->listProducts($this->getVariantsListProductsOperation($remoteId));
    }

    /**
     * Gets the remote commerce ID for a corresponding local product post ID.
     *
     * @param int $localId
     * @return ?string
     */
    protected function getRemoteIdForLocalId(int $localId) : ?string
    {
        return $this->productsMappingService->getRemoteId((new Product())->setId($localId));
    }

    /**
     * Gets the list product operation for variants of a given parent product by ID.
     *
     * @param string $parentId
     * @return ListProductsOperationContract
     */
    protected function getVariantsListProductsOperation(string $parentId) : ListProductsOperationContract
    {
        return ListProductsOperation::getNewInstance()
            ->setParentId($parentId)
            ->setPageSize(100);
    }
}
