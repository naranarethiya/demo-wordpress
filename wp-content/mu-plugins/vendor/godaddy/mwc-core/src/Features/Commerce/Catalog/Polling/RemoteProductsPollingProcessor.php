<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Polling;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers\ListRemoteVariantsJobHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\ListProductsOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\ListProductsService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\Contracts\ListProductsResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Commerce;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingRemoteIdsAfterLocalIdConversionException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Polling\AbstractPollingProcessor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Polling\PollingSupervisor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\AbstractInsertLocalResourceService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\AbstractListRemoteResourcesService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Exceptions\CachingStrategyException;

/**
 * Remote products polling processor.
 *
 * This polling processor is used to poll remote products from Commerce platform services that WooCommerce isn't aware of.
 * The processor will poll periodically for any recently updated products and insert a corresponding record in WooCommerce where applicable.
 * Once a local database record is added, we can overlay further platform API data for those products, even if they are modified externally afterwards.
 *
 * @see PollingSupervisor
 */
class RemoteProductsPollingProcessor extends AbstractPollingProcessor
{
    /** @var string */
    const NAME = 'remote_products';

    /** @var ProductsServiceContract */
    protected ProductsServiceContract $productsService;

    /** @var string */
    public static string $pollingJobConfigName = self::NAME;

    /**
     * Constructor.
     *
     * @param ProductsServiceContract $productsService
     */
    public function __construct(ProductsServiceContract $productsService)
    {
        $this->productsService = $productsService;
    }

    /**
     * We only want to load the processor if the catalog reads are enabled.
     *
     * @return bool
     */
    public static function shouldLoad() : bool
    {
        if (! parent::shouldLoad()) {
            return false;
        }

        return CatalogIntegration::hasCommerceCapability(Commerce::CAPABILITY_READ);
    }

    /**
     * Poll the remote products.
     *
     * This method is called by the supervisor when the polling interval has elapsed.
     * It will query the API for recently updated remote products and insert a corresponding record in WooCommerce where applicable.
     * Specifically, we are interested in remote products created externally that WooCommerce has no knowledge of.
     * This will insert some minimal records in the WPDB, which we can then overlay with further API data afterwards.
     *
     * @param array<string, mixed> $args hook arguments from the last scheduled polling job
     * @return void
     */
    public function poll(array $args = []) : void
    {
        $this->pollingInProgress = true;

        // send a paged Catalog request to list recently updated products
        // this will automatically identify and create local records for remote products that don't exist locally
        try {
            $this->queryProducts($args);
        } catch (MissingRemoteIdsAfterLocalIdConversionException $exception) {
            // this exception is not a reportable error
        } catch (Exception|CommerceExceptionContract $exception) {
            SentryException::getNewInstance($exception->getMessage(), $exception);
        }

        // @TODO in the future we may schedule another job to process the next page(s)

        // bump the last-checked timestamp to avoid polling again during the current interval
        $this->setLastPolledAt();

        $this->pollingInProgress = false;
    }

    /**
     * Queries the API for recently updated products.
     *
     * The act of querying will automatically identify and insert any remote products that don't exist locally.
     * The {@see ListProductsService} which extends the {@see AbstractListRemoteResourcesService} will handle this.
     * The {@see AbstractListRemoteResourcesService::list()} method will trigger the remote association builder,
     * which associates all the remote resources with local counterparts, matching the remote resource IDs (product IDs,
     * in this case) with the local WooCommerce product IDs. If a local product doesn't exist for a remote product,
     * then the {@see AbstractInsertLocalResourceService::insert()} method will be called to insert a new local product.
     *
     * @param array<string, mixed> $args
     * @return ListProductsResponseContract
     * @throws CommerceExceptionContract|CachingStrategyException|BaseException|MissingRemoteIdsAfterLocalIdConversionException
     */
    protected function queryProducts(array $args) : ListProductsResponseContract
    {
        // we query for parent/"top-level" products only
        $response = $this->productsService->listProducts($this->getListProductsOperation($args));

        // this will ensure any variants of those parent products also get imported
        ListRemoteVariantsJobHandler::scheduleIfHasVariants($response);

        return $response;
    }

    /**
     * Gets the page size to list products from the remote platform.
     *
     * @return int
     */
    protected function getListProductsPageSize() : int
    {
        return TypeHelper::int(static::getConfiguration('pollingRequestPageSize'), 50);
    }

    /**
     * Builds the operation to list products, based on the provided pagination arguments.
     *
     * @param array<string, mixed> $args
     * @return ListProductsOperation
     */
    protected function getListProductsOperation(array $args) : ListProductsOperation
    {
        /** @var ListProductsOperation $operation */
        $operation = ListProductsOperation::getNewInstance()
            ->setIncludeChildProducts(false) /* child products are imported separately via {@see ListRemoteVariantsJobHandler} */
            ->setSortBy('updatedAt')
            ->setSortOrder('desc')
            ->setPageSize($this->getListProductsPageSize());

        if (! empty($args['pageToken'])) {
            if (! is_string($args['pageToken'])) {
                new SentryException('Invalid page token provided for remote products polling operation.');
            } else {
                $operation->setPageToken($args['pageToken']);
            }
        }

        return $operation;
    }
}
