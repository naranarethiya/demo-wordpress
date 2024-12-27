<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\DataSources\WooCommerce\Builders\ProductAssociationBuilder;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events\ProductsListedEvent;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers\Contracts\ListProductsCachingHelperContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\ListProductsOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\Contracts\CatalogProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductAssociation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductRequestInputs\ListProductsInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ListProductsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsCachingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\GatewayRequestException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Operations\Contracts\ListRemoteResourcesOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\ProductMapRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\AbstractListRemoteResourcesService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\CanBroadcastResourceEventsTrait;

/**
 * List products service.
 */
class ListProductsService extends AbstractListRemoteResourcesService implements ListProductsServiceContract
{
    use CanBroadcastResourceEventsTrait;

    /** @var CommerceContextContract */
    protected CommerceContextContract $commerceContext;

    /** @var CatalogProviderContract */
    protected CatalogProviderContract $catalogProvider;

    /**
     * Constructor.
     */
    public function __construct(
        ProductMapRepository $productMapRepository,
        CommerceContextContract $commerceContext,
        CatalogProviderContract $catalogProvider,
        ListProductsCachingHelperContract $listProductsCachingHelper,
        ProductsCachingServiceContract $productsCachingService,
        ProductAssociationBuilder $productAssociationBuilder
    ) {
        $this->commerceContext = $commerceContext;
        $this->catalogProvider = $catalogProvider;

        parent::__construct(
            $productMapRepository,
            $listProductsCachingHelper,
            $productsCachingService,
            $productAssociationBuilder
        );
    }

    /**
     * Executes the list query via the platform API.
     *
     * @param ListProductsOperationContract $operation
     * @return ProductBase[]
     * @throws GatewayRequestException
     */
    protected function executeListQuery(ListRemoteResourcesOperationContract $operation) : array
    {
        return $this->catalogProvider->products()->list($this->getListProductsInput($operation));
    }

    /**
     * Gets the list products input.
     *
     * Assembles data used to inform product list requests sent to the Catalog API.
     *
     * @param ListProductsOperationContract $listProductsOperation contains the query args to use to list products
     * @return ListProductsInput the DTO to use to list products
     */
    protected function getListProductsInput(ListProductsOperationContract $listProductsOperation) : ListProductsInput
    {
        return new ListProductsInput([
            'queryArgs' => $listProductsOperation->toArray(), // Note: this implementation of `toArray()` removes keys with null values.
            'storeId'   => $this->commerceContext->getStoreId(),
        ]);
    }

    /**
     * Maybe broadcast a ProductsListedEvent event with the given association objects.
     *
     * @param ProductAssociation[] $resourceAssociations
     * @return void
     */
    protected function maybeBroadcastListEvent(array $resourceAssociations) : void
    {
        $this->maybeBroadcastEvent(CatalogIntegration::class, ProductsListedEvent::getNewInstance($resourceAssociations));
    }
}
