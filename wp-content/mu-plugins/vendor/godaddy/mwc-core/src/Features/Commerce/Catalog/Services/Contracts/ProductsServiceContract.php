<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\CreateOrUpdateProductOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\ListProductsOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\PatchProductOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\ReadProductBySkuOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\ReadProductOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\Contracts\CreateOrUpdateProductResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\Contracts\ListProductsResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\Contracts\ReadProductResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\CommerceException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\GatewayRequest404Exception;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\GatewayRequestException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingRemoteIdsAfterLocalIdConversionException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\ProductMappingNotFoundException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\ProductNotCreatableException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\ProductNotFoundException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\ProductNotFoundForGivenSkuException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Exceptions\CachingStrategyException;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;
use WP_Post;

/**
 * Contract for catalog product services.
 */
interface ProductsServiceContract
{
    /**
     * Creates or updates a product.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @return CreateOrUpdateProductResponseContract
     * @throws GatewayRequestException|ProductNotCreatableException
     */
    public function createOrUpdateProduct(CreateOrUpdateProductOperationContract $operation) : CreateOrUpdateProductResponseContract;

    /**
     * Creates a product.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @return CreateOrUpdateProductResponseContract
     * @throws GatewayRequestException|ProductNotCreatableException
     */
    public function createProduct(CreateOrUpdateProductOperationContract $operation) : CreateOrUpdateProductResponseContract;

    /**
     * Reads a product by the local ID.
     *
     * @param ReadProductOperationContract $operation
     * @return ReadProductResponseContract
     * @throws MissingProductRemoteIdException|GatewayRequest404Exception|GatewayRequestException|ProductMappingNotFoundException
     * @throws ProductNotFoundException
     */
    public function readProduct(ReadProductOperationContract $operation) : ReadProductResponseContract;

    /**
     * Reads a product by SKU.
     *
     * @param ReadProductBySkuOperationContract $operation
     * @return ReadProductResponseContract
     * @throws GatewayRequestException
     * @throws GatewayRequest404Exception
     * @throws ProductNotFoundForGivenSkuException
     */
    public function readProductBySku(ReadProductBySkuOperationContract $operation) : ReadProductResponseContract;

    /**
     * Lists products.
     *
     * @param ListProductsOperationContract $operation
     * @return ListProductsResponseContract
     * @throws CommerceExceptionContract|CachingStrategyException|BaseException|MissingRemoteIdsAfterLocalIdConversionException
     */
    public function listProducts(ListProductsOperationContract $operation) : ListProductsResponseContract;

    /**
     * List products for a given array of local IDs.
     *
     * @param int[] $localIds
     *
     * @return ListProductsResponseContract
     * @throws CommerceExceptionContract|CachingStrategyException|BaseException|MissingRemoteIdsAfterLocalIdConversionException
     */
    public function listProductsByLocalIds(array $localIds) : ListProductsResponseContract;

    /**
     * Updates a product.
     *
     * This updates/overwrites _all_ of the product's properties. To only update some, see {@see static::patchProduct()}.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @param string $remoteId
     * @return CreateOrUpdateProductResponseContract
     * @throws GatewayRequest404Exception|GatewayRequestException
     */
    public function updateProduct(CreateOrUpdateProductOperationContract $operation, string $remoteId) : CreateOrUpdateProductResponseContract;

    /**
     * Update the remote product using the WP_Post.
     *
     * @param WP_Post $productPost
     * @param callable(Product $nativeProduct): CreateOrUpdateProductOperationContract $operationCallback Callback that returns a {@see CreateOrUpdateProductResponseContract}
     * @return void
     * @throws MissingProductRemoteIdException|CommerceException|Exception
     */
    public function updateProductFromWpPost(WP_Post $productPost, callable $operationCallback) : void;

    /**
     * Patches a product.
     *
     * This only updates _some_ of the product properties (provided in the {@see PatchProductOperationContract}.
     *
     * @param PatchProductOperationContract $operation
     * @param string $remoteId
     * @return CreateOrUpdateProductResponseContract
     * @throws GatewayRequest404Exception|GatewayRequestException|MissingProductRemoteIdException|CachingStrategyException|CommerceExceptionContract
     * @throws ProductNotFoundException
     */
    public function patchProduct(PatchProductOperationContract $operation, string $remoteId) : CreateOrUpdateProductResponseContract;
}
