<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Polling;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\ListCategoriesOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Categories\CategoryAssociation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ListCategoriesServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\ListCategoriesService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Commerce;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingRemoteIdsAfterLocalIdConversionException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Polling\AbstractPollingProcessor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Polling\PollingSupervisor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\AbstractInsertLocalResourceService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\AbstractListRemoteResourcesService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Exceptions\CachingStrategyException;

/**
 * Remote categories polling processor.
 *
 * This polling processor is used to poll remote categories from Commerce platform services that WooCommerce isn't aware of.
 * The processor will poll periodically categories and insert a corresponding record in WooCommerce where applicable.
 * Once a local database record is added, we can overlay further platform API data for those categories, even if they are modified externally afterward.
 *
 * @see PollingSupervisor
 */
class RemoteCategoriesPollingProcessor extends AbstractPollingProcessor
{
    /** @var string */
    const NAME = 'remote_categories';

    /** @var ListCategoriesServiceContract list categories service */
    protected ListCategoriesServiceContract $listCategoriesService;

    /** @var string */
    public static string $pollingJobConfigName = self::NAME;

    /**
     * Constructor.
     *
     * @param ListCategoriesServiceContract $listCategoriesService
     */
    public function __construct(ListCategoriesServiceContract $listCategoriesService)
    {
        $this->listCategoriesService = $listCategoriesService;
    }

    /**
     * We only want to load the processor if the catalog reads are enabled.
     *
     * @return bool
     */
    public static function shouldLoad() : bool
    {
        return parent::shouldLoad()
            && CatalogIntegration::hasCommerceCapability(Commerce::CAPABILITY_READ);
    }

    /**
     * Poll the remote categories.
     *
     * This method is called by the supervisor when the polling interval has elapsed.
     * It will query the API for recently updated remote categories and insert a corresponding record where applicable.
     * Specifically, we are interested in remote categories created externally that WooCommerce/WordPress have no knowledge of.
     * This will insert some minimal records in the WPDB, which we can then overlay with further API data afterward.
     *
     * @param array<string, mixed> $args hook arguments from the last scheduled polling job
     * @return void
     */
    public function poll(array $args = []) : void
    {
        $this->pollingInProgress = true;

        // send a paged Catalog request to list categories
        // this will automatically identify and create local records for remote categories that don't exist locally
        try {
            $this->queryCategories($args);
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
     * Queries the API for categories.
     *
     * The act of querying will automatically identify and insert any remote categories that don't exist locally.
     * The {@see ListCategoriesService} which extends the {@see AbstractListRemoteResourcesService} will handle this.
     * The {@see AbstractListRemoteResourcesService::list()} method will trigger the remote association builder,
     * which associates all the remote resources with local counterparts, matching the remote resource IDs (category IDs,
     * in this case) with the local WooCommerce product category IDs. If a local category doesn't exist for a remote category,
     * then the {@see AbstractInsertLocalResourceService::insert()} method will be called to insert a new local category.
     *
     * @param array<string, mixed> $args
     * @return CategoryAssociation[]
     * @throws CommerceExceptionContract|CachingStrategyException|BaseException|MissingRemoteIdsAfterLocalIdConversionException
     */
    protected function queryCategories(array $args) : array
    {
        return $this->listCategoriesService->list($this->getListCategoriesOperation($args));
    }

    /**
     * Gets the page size to list categories from the remote platform.
     *
     * @return int
     */
    protected function getListCategoriesPageSize() : int
    {
        return TypeHelper::int(static::getConfiguration('pollingRequestPageSize'), 50);
    }

    /**
     * Builds the operation to list categories, based on the provided pagination arguments.
     *
     * @param array<string, mixed> $args
     * @return ListCategoriesOperation
     */
    protected function getListCategoriesOperation(array $args) : ListCategoriesOperation
    {
        $operation = ListCategoriesOperation::getNewInstance()
            ->setSortBy('updatedAt')
            ->setSortOrder('desc')
            ->setPageSize($this->getListCategoriesPageSize());

        if (! empty($args['pageToken'])) {
            if (! is_string($args['pageToken'])) {
                SentryException::getNewInstance('Invalid page token provided for remote categories polling operation.');
            } else {
                $operation->setPageToken($args['pageToken']);
            }
        }

        return $operation;
    }
}
