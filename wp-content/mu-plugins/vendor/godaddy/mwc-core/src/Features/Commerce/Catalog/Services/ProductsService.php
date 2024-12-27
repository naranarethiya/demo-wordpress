<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\AdapterException;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\ProductsRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\DataStores\Traits\HasProductPlatformDataStoreCrudTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events\ProductCreatedEvent;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events\ProductReadEvent;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events\ProductUpdatedEvent;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\CreateOrUpdateProductOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\ListProductsOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\PatchProductOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\ReadProductBySkuOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\ReadProductOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\ListProductsOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\ReadProductBySkuOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\Contracts\CatalogProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ChannelIds;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductAssociation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductRequestInputs\CreateProductInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductRequestInputs\ListProductsInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductRequestInputs\PatchProductInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductRequestInputs\ReadProductInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductRequestInputs\UpdateProductInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters\ProductBaseAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ListProductsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsCachingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\Contracts\CreateOrUpdateProductResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\Contracts\ListProductsResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\Contracts\ReadProductResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\CreateOrUpdateProductResponse;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\ListProductsResponse;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\ReadProductResponse;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Commerce;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\CommerceException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\GatewayRequest404Exception;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\GatewayRequestException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductLocalIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdForParentException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingRemoteIdsAfterLocalIdConversionException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\NotUniqueException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\ProductMappingNotFoundException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\ProductNotCreatableException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\ProductNotFoundForGivenSkuException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Exceptions\CachingStrategyException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\CanBroadcastResourceEventsTrait;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\ProductAdapter;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;
use WP_Post;

/**
 * Handles communication between Managed WooCommerce and the commerce catalog API for CRUD operations.
 */
class ProductsService implements ProductsServiceContract
{
    use CanBroadcastResourceEventsTrait;

    /** @var CommerceContextContract context of the current site - contains the store ID */
    protected CommerceContextContract $commerceContext;

    /** @var CatalogProviderContract provider to the external API's CRUD operations */
    protected CatalogProviderContract $productsProvider;

    /** @var ProductsMappingServiceContract service that handles mapping local entities to their remote equivalents */
    protected ProductsMappingServiceContract $productsMappingService;

    /** @var ListProductsServiceContract service to list products */
    protected ListProductsServiceContract $listProductsService;

    /** @var ProductsCachingServiceContract service for caching remote product objects */
    protected ProductsCachingServiceContract $productsCachingService;

    /** @var ProductBaseAdapter adapter to convert between {@see ProductBase} and {@see Product} objects */
    protected ProductBaseAdapter $productBaseAdapter;

    /** @var PoyntProductAssociationService service to aid in making associations with Poynt products */
    protected PoyntProductAssociationService $poyntProductAssociationService;

    /**
     * Constructor.
     *
     * @param CommerceContextContract $commerceContext
     * @param CatalogProviderContract $productsProvider
     * @param ProductsMappingServiceContract $productsMappingService
     * @param ListProductsServiceContract $listProductsService
     * @param ProductsCachingServiceContract $productsCachingService
     * @param ProductBaseAdapter $productBaseAdapter
     * @param PoyntProductAssociationService $poyntProductAssociationService
     */
    final public function __construct(
        CommerceContextContract $commerceContext,
        CatalogProviderContract $productsProvider,
        ProductsMappingServiceContract $productsMappingService,
        ListProductsServiceContract $listProductsService,
        ProductsCachingServiceContract $productsCachingService,
        ProductBaseAdapter $productBaseAdapter,
        PoyntProductAssociationService $poyntProductAssociationService
    ) {
        $this->commerceContext = $commerceContext;
        $this->productsProvider = $productsProvider;
        $this->productsMappingService = $productsMappingService;
        $this->listProductsService = $listProductsService;
        $this->productsCachingService = $productsCachingService;
        $this->productBaseAdapter = $productBaseAdapter;
        $this->poyntProductAssociationService = $poyntProductAssociationService;
    }

    /**
     * Reads a product from the remote service by the local ID.
     *
     * @param ReadProductOperationContract $operation
     * @return ReadProductResponseContract
     * @throws CommerceExceptionContract|ProductMappingNotFoundException|Exception|GatewayRequest404Exception|MissingProductRemoteIdException
     */
    public function readProduct(ReadProductOperationContract $operation) : ReadProductResponseContract
    {
        $remoteId = $this->productsMappingService->getRemoteId(Product::getNewInstance()->setId($operation->getLocalId()));

        if (! $remoteId) {
            throw new ProductMappingNotFoundException('No local mapping found for product.');
        }

        $product = $this->readProductFromProvider($remoteId);

        $this->maybeBroadcastEvent(CatalogIntegration::class, ProductReadEvent::getNewInstance(
            ProductAssociation::getNewInstance([
                'remoteResource' => $product,
                'localId'        => $operation->getLocalId(),
            ])
        ));

        return ReadProductResponse::getNewInstance($product);
    }

    /**
     * Reads a product from the provider by its remote UUID.
     *
     * @param string $remoteId
     * @return ProductBase
     * @throws CachingStrategyException|CommerceExceptionContract
     * @phpstan-ignore-next-line phpstan isn't recoginizing the above exceptions are thrown, but they are (from the callback!)
     */
    protected function readProductFromProvider(string $remoteId) : ProductBase
    {
        return $this->productsCachingService->remember(
            $remoteId,
            fn () => $this->productsProvider->products()->read($this->getReadProductInput($remoteId))
        );
    }

    /**
     * Gets the input for the create product operation.
     *
     * @param string $remoteId
     * @return ReadProductInput
     */
    protected function getReadProductInput(string $remoteId) : ReadProductInput
    {
        return ReadProductInput::getNewInstance([
            'productId' => $remoteId,
            'storeId'   => $this->commerceContext->getStoreId(),
        ]);
    }

    /**
     * Reads a product from the remote service by SKU.
     *
     * @param ReadProductBySkuOperationContract $operation
     * @return ReadProductResponseContract
     * @throws GatewayRequest404Exception|GatewayRequestException
     * @throws ProductNotFoundForGivenSkuException
     */
    public function readProductBySku(ReadProductBySkuOperationContract $operation) : ReadProductResponseContract
    {
        $operation = ListProductsOperation::getNewInstance()->setSku($operation->getSku())->setPageSize(1);

        // not using ProductsService::listProducts() here because we do not need to build local <=> remote associations,
        // nor do we want to import non-Woo products in this scenario.
        $products = $this->productsProvider->products()->list(
            new ListProductsInput([
                'queryArgs' => $operation->toArray(),
                'storeId'   => $this->commerceContext->getStoreId(),
            ])
        );

        if (! empty($products[0])) {
            /*
             * @TODO Determine if we should broadcast a {@see ProductReadEvent} here, like we do in {@see static::readProduct()}.
             * That event requires having a local ID for the product. But when we call this method to read by SKU, we
             * don't necessarily have a corresponding local product at that point.
             * Due to this method's limited usage, _not_ broadcasting the event doesn't really hurt us. But in the future
             * we may want to reconsider.
             * {agibson 2023-12-08}
             */
            return ReadProductResponse::getNewInstance($products[0]);
        } else {
            throw new ProductNotFoundForGivenSkuException("No product found with SKU {$operation->getSku()}");
        }
    }

    /**
     * Creates or updates the product.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @return CreateOrUpdateProductResponseContract
     * @throws MissingProductLocalIdException|MissingProductRemoteIdException|CommerceExceptionContract|AdapterException|Exception|ProductNotCreatableException
     */
    public function createOrUpdateProduct(CreateOrUpdateProductOperationContract $operation) : CreateOrUpdateProductResponseContract
    {
        $localId = $operation->getLocalId();

        if (! $localId) {
            throw new MissingProductLocalIdException('The product has no local ID.');
        }

        if ($remoteId = $this->productsMappingService->getRemoteId($operation->getProduct())) {
            return $this->updateProduct($operation, $remoteId);
        } else {
            $operation->setChannelIds(ChannelIds::getNewInstance([
                'add' => [Commerce::getChannelId()],
            ]));

            return $this->createProduct($operation);
        }
    }

    /**
     * Updates the product in the remote service.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @param string $remoteId
     * @return CreateOrUpdateProductResponseContract
     * @throws AdapterException|CommerceException|CommerceExceptionContract|MissingProductRemoteIdException|Exception
     */
    public function updateProduct(CreateOrUpdateProductOperationContract $operation, string $remoteId) : CreateOrUpdateProductResponseContract
    {
        $product = $this->productsProvider->products()->update($this->getUpdateProductInput($operation, $remoteId));

        if (! isset($product->productId) || ! $product->productId) {
            throw MissingProductRemoteIdException::withDefaultMessage();
        }

        $this->productsCachingService->remove($remoteId);

        $response = new CreateOrUpdateProductResponse($product->productId, $product);

        $this->maybeBroadcastEvent(CatalogIntegration::class, ProductUpdatedEvent::getNewInstance(
            ProductAssociation::getNewInstance([
                'remoteResource' => $product,
                'localId'        => $operation->getLocalId(),
            ])
        ));

        return $response;
    }

    /**
     * Update a remote product using a WP_Post object.
     *
     * The `$productPost` is converted to its native product model, then `$operationCallback` is called with this
     * native product model. The callback allows for flexibility in how the operation is composed.
     *
     * @param WP_Post $productPost
     * @param callable(Product $nativeProduct): CreateOrUpdateProductOperationContract $operationCallback Callback that returns a {@see CreateOrUpdateProductResponseContract}
     * @return void
     * @throws CommerceExceptionContract|MissingProductRemoteIdException|AdapterException|Exception
     */
    public function updateProductFromWpPost(WP_Post $productPost, callable $operationCallback) : void
    {
        $sourceProduct = ProductsRepository::get($productPost->ID);
        if (! $sourceProduct) {
            throw new CommerceException(sprintf('Unable to update product from WP_Post, failed to fetch product %d.', $productPost->ID));
        }

        $nativeProduct = ProductAdapter::getNewInstance($sourceProduct)->convertFromSource();
        $remoteId = $this->productsMappingService->getRemoteId($nativeProduct);
        if (! $remoteId) {
            throw MissingProductRemoteIdException::withDefaultMessage();
        }

        /** @var CreateOrUpdateProductOperationContract $operation */
        $operation = $operationCallback($nativeProduct);

        $this->updateProduct($operation, $remoteId);
    }

    /**
     * {@inheritDoc}
     */
    public function patchProduct(PatchProductOperationContract $operation, string $remoteId) : CreateOrUpdateProductResponseContract
    {
        $product = $this->productsProvider->products()->patch($this->getPatchProductInput($operation, $remoteId));

        if (! isset($product->productId) || ! $product->productId) {
            throw MissingProductRemoteIdException::withDefaultMessage();
        }

        $this->productsCachingService->remove($remoteId);

        $this->maybeBroadcastEvent(CatalogIntegration::class, ProductUpdatedEvent::getNewInstance(
            ProductAssociation::getNewInstance([
                'remoteResource' => $product,
                'localId'        => $operation->getLocalProductId(),
            ])
        ));

        return new CreateOrUpdateProductResponse($product->productId, $product);
    }

    /**
     * Makes a {@see PatchProductInput} from the supplied operation.
     *
     * @param PatchProductOperationContract $operation
     * @param string $remoteId
     * @return PatchProductInput
     */
    protected function getPatchProductInput(PatchProductOperationContract $operation, string $remoteId) : PatchProductInput
    {
        return new PatchProductInput([
            'storeId'    => $this->commerceContext->getStoreId(),
            'productId'  => $remoteId,
            'properties' => ArrayHelper::except($operation->toArray(), ['localProductId']), // this gives us only the properties that were _set_ on the operation class
        ]);
    }

    /**
     * Creates the product in the remote service.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @return CreateOrUpdateProductResponseContract
     * @throws AdapterException|CommerceException|CommerceExceptionContract|MissingProductRemoteIdException|Exception
     */
    public function createProduct(CreateOrUpdateProductOperationContract $operation) : CreateOrUpdateProductResponseContract
    {
        $this->validateShouldCreateProduct($operation);

        $product = $this->createProductOrUpdateExisting($operation);

        if (! isset($product->productId) || ! $product->productId) {
            throw MissingProductRemoteIdException::withDefaultMessage();
        }

        $this->productsMappingService->saveRemoteId($operation->getProduct(), $product->productId);

        $this->maybeBroadcastEvent(
            CatalogIntegration::class,
            ProductCreatedEvent::getNewInstance($operation->getProduct(), $product->productId, $product)
        );

        return new CreateOrUpdateProductResponse($product->productId, $product);
    }

    /**
     * Performs last-minute checks to ensure we should create a product.
     * See also {@see HasProductPlatformDataStoreCrudTrait::shouldWriteProductToCatalog()} which handles more generic
     * "should we write this at all" conditions. Whereas this check is specific to _creating_ products only.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @return void
     * @throws ProductNotCreatableException
     */
    protected function validateShouldCreateProduct(CreateOrUpdateProductOperationContract $operation) : void
    {
        if ('draft' === $operation->getProduct()->getStatus()) {
            throw new ProductNotCreatableException('Draft products cannot be created in the platform.');
        }
    }

    /**
     * Creates a new product in the remote service, or updates the existing one.
     *
     * If attempting to create throws a {@see NotUniqueException}, then we find the remote product with the same SKU
     * and attempt to confirm that they are intentionally the same product. If so, we'll update that remote product
     * and return it.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @return ProductBase
     * @throws AdapterException|CommerceException|CommerceExceptionContract|NotUniqueException|Exception
     */
    protected function createProductOrUpdateExisting(CreateOrUpdateProductOperationContract $operation) : ProductBase
    {
        try {
            return $this->productsProvider->products()->create($this->getCreateProductInput($operation));
        } catch(NotUniqueException $e) {
            // find the remote product that has this same SKU & attempt to confirm they are intentionally the same product
            $matchingRemoteProduct = $this->findExistingRemoteProductThatMatchesLocal($operation->getProduct());

            if ($matchingRemoteProduct && $matchingRemoteProduct->productId) {
                // update the remote product so that it gets the latest Woo changes
                return $this->productsProvider->products()->update($this->getUpdateProductInput($operation, $matchingRemoteProduct->productId));
            }

            throw $e;
        }
    }

    /**
     * Finds a remote product with the same SKU as the provided local product, and attempts to confirm that they are
     * intentionally the same product. We can do this by checking if it's a product that has previously been synced
     * with Poynt {@see PoyntProductAssociationService::getLocalPoyntProductForRemoteResource()}.
     *
     * @param Product $localProduct
     * @return ProductBase|null
     * @throws GatewayRequest404Exception|GatewayRequestException
     * @throws ProductNotFoundForGivenSkuException
     */
    protected function findExistingRemoteProductThatMatchesLocal(Product $localProduct) : ?ProductBase
    {
        $remoteProduct = $this->readProductBySku(
            ReadProductBySkuOperation::getNewInstance()->setSku($localProduct->getSku())
        )->getProduct();

        // check if it was a product previously synced with Poynt -- this is how we can confirm they're intentionally the same product
        $correspondingLocalProduct = $this->poyntProductAssociationService->getLocalPoyntProductForRemoteResource($remoteProduct);

        if ($correspondingLocalProduct && $correspondingLocalProduct->getId() === $localProduct->getId()) {
            return $remoteProduct;
        }

        // this means there is a remote product with the same SKU but we can't confirm it's definitely the same as the local version!
        return null;
    }

    /**
     * Lists products.
     *
     * @param ListProductsOperationContract $operation
     *
     * @return ListProductsResponseContract
     * @throws CommerceExceptionContract|CachingStrategyException|BaseException|MissingRemoteIdsAfterLocalIdConversionException
     */
    public function listProducts(ListProductsOperationContract $operation) : ListProductsResponseContract
    {
        return new ListProductsResponse($this->listProductsService->list($operation));
    }

    /**
     * {@inheritDoc}
     */
    public function listProductsByLocalIds(array $localIds) : ListProductsResponseContract
    {
        return $this->listProducts(
            ListProductsOperation::getNewInstance()
                ->setLocalIds($localIds)
        );
    }

    /**
     * Creates an instance of {@see UpdateProductInput} using the information from the product in the given operation.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @param string $remoteId
     * @return UpdateProductInput
     * @throws AdapterException|CommerceException|Exception
     */
    protected function getUpdateProductInput(CreateOrUpdateProductOperationContract $operation, string $remoteId) : UpdateProductInput
    {
        $productData = $this->getProductData($operation->getProduct(), $remoteId);

        if (! $productData) {
            throw new CommerceException('Unable to prepare product input data.');
        }

        $productData->productId = $remoteId;

        return new UpdateProductInput([
            'product'    => $productData,
            'storeId'    => $this->commerceContext->getStoreId(),
            'channelIds' => $operation->getChannelIds(),
        ]);
    }

    /**
     * Creates an instance of {@see CreateProductInput} using the information from the product in the given operation.
     *
     * @param CreateOrUpdateProductOperationContract $operation
     * @return CreateProductInput
     * @throws AdapterException|CommerceException|Exception
     */
    protected function getCreateProductInput(CreateOrUpdateProductOperationContract $operation) : CreateProductInput
    {
        $productData = $this->getProductData($operation->getProduct());

        if (! $productData) {
            throw new CommerceException('Unable to prepare product input data.');
        }

        return new CreateProductInput([
            'product'    => $productData,
            'storeId'    => $this->commerceContext->getStoreId(),
            'channelIds' => $operation->getChannelIds(),
        ]);
    }

    /**
     * Attempts to create a product data object for the given MWC Product.
     *
     * @param Product $product
     * @param string|null $remoteId
     * @return ProductBase
     * @throws AdapterException|Exception|MissingProductRemoteIdForParentException
     */
    protected function getProductData(Product $product, ?string $remoteId = null) : ?ProductBase
    {
        try {
            $remoteProduct = $remoteId ? $this->readProductFromProvider($remoteId) : null;
        } catch(Exception|CommerceExceptionContract $e) {
            $remoteProduct = null;
        }

        return $this->productBaseAdapter->convertToSource($product, $remoteProduct);
    }
}
