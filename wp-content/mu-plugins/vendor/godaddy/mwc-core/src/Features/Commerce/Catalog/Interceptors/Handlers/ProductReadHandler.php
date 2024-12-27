<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\Handlers\AbstractInterceptorHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers\RemoteProductNotFoundHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\ProductReadInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\ReadProductOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductPost;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters\ProductPostAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Commerce;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\CommerceException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\GatewayRequest404Exception;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\GatewayRequestException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingProductRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\ProductMappingNotFoundException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\ProductNotFoundException;
use stdClass;

/**
 * Callback handler for {@see ProductReadInterceptor}.
 *
 * This is used to overlay remote product data with local product post objects.
 */
class ProductReadHandler extends AbstractInterceptorHandler
{
    /** @var ProductsServiceContract */
    protected ProductsServiceContract $productsService;

    /** @var ProductPostAdapter */
    protected ProductPostAdapter $postAdapter;

    protected RemoteProductNotFoundHelper $remoteProductNotFoundHelper;

    /**
     * Constructor.
     *
     * @param ProductsServiceContract $productsService
     * @param ProductPostAdapter $postAdapter
     * @param RemoteProductNotFoundHelper $remoteProductNotFoundHelper
     */
    public function __construct(ProductsServiceContract $productsService, ProductPostAdapter $postAdapter, RemoteProductNotFoundHelper $remoteProductNotFoundHelper)
    {
        $this->productsService = $productsService;
        $this->postAdapter = $postAdapter;
        $this->remoteProductNotFoundHelper = $remoteProductNotFoundHelper;
    }

    /**
     * Determines whether it should read a product post object from catalog.
     *
     * @param mixed|stdClass $post post object from wpdb
     * @return bool
     */
    protected function shouldRead($post) : bool
    {
        $productPostTypes = [
            CatalogIntegration::PRODUCT_POST_TYPE,
            CatalogIntegration::PRODUCT_VARIATION_POST_TYPE,
        ];

        return is_object($post)
            && isset($post->post_type)
            && ArrayHelper::contains($productPostTypes, $post->post_type)
            && ! $this->isProductBeingEdited($post)
            && CatalogIntegration::hasCommerceCapability(Commerce::CAPABILITY_READ);
    }

    /**
     * Determines whether a product is currently being edited via the admin UI.
     * This check is necessary to prevent reading from the platform while we're in the process of editing a product.
     *
     * @TODO we should ideally find a better, more reliable way to disable reads during the edit process {agibson 2023-05-03}
     *
     * @param object $post
     * @return bool
     */
    protected function isProductBeingEdited(object $post) : bool
    {
        return isset($_REQUEST['_wpnonce']) && ! empty($post->ID) && wp_verify_nonce($_REQUEST['_wpnonce'], "update-post_{$post->ID}");
    }

    /**
     * Reads a product post object from catalog.
     *
     * @param array<int, mixed> $args arguments passed to the filter
     * @return stdClass|mixed the product post object from catalog or the original post object
     */
    public function run(...$args)
    {
        // not a product post object
        if (! $this->shouldRead($args[0])) {
            return $args[0];
        }

        /** @var stdClass $sourcePostObject */
        $sourcePostObject = $args[0];

        try {
            return $this->buildOverlaidPostObject($sourcePostObject);
        } catch (ProductNotFoundException $exception) {
            if (! empty($sourcePostObject->ID)) {
                $this->remoteProductNotFoundHelper->handle($sourcePostObject->ID);
            }

            // false indicates the product failed to be retrieved
            return false;
        } catch(ProductMappingNotFoundException $exception) {
            // Indicates the product has not been written to the platform yet. For now we do not need to report this.
            // Return the original post object as-is.
            return $sourcePostObject;
        } catch (Exception $exception) {
            SentryException::getNewInstance($exception->getMessage(), $exception);

            // return the original post object as-is
            return $sourcePostObject;
        }
    }

    /**
     * Builds a new WP_Post-style object, with data overlaid from the platform.
     *
     * This performs the following:
     *  - Executes an API request to get the platform-equivalent product of a local product ID {@see static::getProductBaseFromLocalId()}
     *  - Adapts that {@see ProductBase} instance into a {@see \WP_Post} object.
     *  - Overlays our WP_Post data on top of the original object, so that platform values override local ones.
     *  - Then returns the resulting object.
     *
     * @param stdClass $sourcePostObject
     * @return stdClass
     * @throws CommerceException|MissingProductRemoteIdException|GatewayRequest404Exception|GatewayRequestException|ProductMappingNotFoundException
     * @throws ProductNotFoundException
     */
    protected function buildOverlaidPostObject(stdClass $sourcePostObject) : stdClass
    {
        $productId = TypeHelper::int($sourcePostObject->ID ?? 0, 0);
        if (empty($productId)) {
            throw new CommerceException('No local product ID found.');
        }

        $productBase = $this->getProductBaseFromLocalId($productId);
        $productPost = $this->postAdapter->setLocalPost((array) $sourcePostObject)->convertToSource($productBase);

        // in theory at this point we should have a well-formed object if no exceptions were thrown, but this is a safety check
        if (! $productPost instanceof ProductPost) {
            throw new CommerceException("Could not build product post object from catalog for local product ID {$productId}.");
        }

        // overlays the source post object from the WordPress database with an object from Commerce catalog
        return $productPost->toDatabaseObject($sourcePostObject);
    }

    /**
     * Given a local product ID, performs an API request to get the remote equivalent from the platform and returns
     * a {@see ProductBase} object.
     *
     * @param int $localProductId
     * @return ProductBase
     * @throws CommerceException|GatewayRequest404Exception|MissingProductRemoteIdException|GatewayRequestException|ProductMappingNotFoundException
     * @throws ProductNotFoundException
     */
    protected function getProductBaseFromLocalId(int $localProductId) : ProductBase
    {
        if (! $localProductId) {
            throw new CommerceException('Invalid local product ID to build product post object from catalog.');
        }

        $operation = ReadProductOperation::seed(['localId' => $localProductId]);

        return $this->productsService->readProduct($operation)->getProduct();
    }
}
