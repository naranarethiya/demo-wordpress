<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\DataSources\WooCommerce\Builders\CategoryAssociationBuilder;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers\ListCategoriesCachingHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\ListCategoriesOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Categories\Category;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Categories\CategoryAssociation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\CategoriesCachingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\CategoriesServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ListCategoriesServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\GatewayRequest404Exception;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Operations\Contracts\ListRemoteResourcesOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\CategoryMapRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\AbstractListRemoteResourcesService;

/**
 * List categories service.
 *
 * @method CategoryAssociation[] list(ListRemoteResourcesOperationContract $operation)
 */
class ListCategoriesService extends AbstractListRemoteResourcesService implements ListCategoriesServiceContract
{
    /** @var CategoriesServiceContract categories service */
    protected CategoriesServiceContract $categoriesService;

    /**
     * Constructor.
     *
     * @param CategoryMapRepository $resourceMapRepository
     * @param ListCategoriesCachingHelper $listRemoteResourcesCachingHelperContract
     * @param CategoriesCachingServiceContract $cachingService
     * @param CategoryAssociationBuilder $resourceAssociationBuilder
     * @param CategoriesServiceContract $categoriesService
     */
    public function __construct(
        CategoryMapRepository $resourceMapRepository,
        ListCategoriesCachingHelper $listRemoteResourcesCachingHelperContract,
        CategoriesCachingServiceContract $cachingService,
        CategoryAssociationBuilder $resourceAssociationBuilder,
        CategoriesServiceContract $categoriesService
    ) {
        $this->categoriesService = $categoriesService;

        parent::__construct($resourceMapRepository, $listRemoteResourcesCachingHelperContract, $cachingService, $resourceAssociationBuilder);
    }

    /**
     * Executes the list query via the platform API.
     *
     * @param ListCategoriesOperationContract $operation
     * @return Category[]
     * @throws GatewayRequest404Exception
     */
    protected function executeListQuery(ListRemoteResourcesOperationContract $operation) : array
    {
        $response = $this->categoriesService->listCategories($operation);

        return $response->getCategories();
    }
}
