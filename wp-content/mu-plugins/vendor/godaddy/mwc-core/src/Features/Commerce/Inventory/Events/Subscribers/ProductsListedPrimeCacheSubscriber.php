<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Events\Subscribers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\SubscriberContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events\ProductsListedEvent;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductAssociation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Commerce;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\InventoryIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\LevelsServiceWithCacheContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\SummariesServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\ListLevelsByRemoteIdOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\ListSummariesOperation;

class ProductsListedPrimeCacheSubscriber implements SubscriberContract
{
    protected SummariesServiceContract $summariesService;
    protected LevelsServiceWithCacheContract $levelsService;

    /**
     * @param SummariesServiceContract $summariesService
     * @param LevelsServiceWithCacheContract $levelsService
     */
    public function __construct(
        SummariesServiceContract $summariesService,
        LevelsServiceWithCacheContract $levelsService
    ) {
        $this->summariesService = $summariesService;
        $this->levelsService = $levelsService;
    }

    /**
     * {@inheritDoc}
     *
     * @param ProductsListedEvent $event
     */
    public function handle(EventContract $event) : void
    {
        if (! $this->isValid($event) || ! $this->shouldHandle()) {
            return;
        }

        if (! $productIds = $this->getProductIds($event->productAssociations)) {
            return;
        }

        try {
            $this->summariesService->list(ListSummariesOperation::seed([
                'productIds' => $productIds,
            ]));

            $this->levelsService->listLevelsByRemoteProductId(ListLevelsByRemoteIdOperation::seed([
                'ids' => $productIds,
            ]));
        } catch (Exception|CommerceExceptionContract $exception) {
            SentryException::getNewInstance('Could not prime inventory caches', $exception);
        }
    }

    /**
     * Valid events are {@see ProductsListedEvent} where the productAssociations array is not empty.
     *
     * @param EventContract $event
     * @return bool
     */
    public function isValid(EventContract $event) : bool
    {
        return $event instanceof ProductsListedEvent && ! empty($event->productAssociations);
    }

    /**
     * Events should only be handled if the inventory integration is enabled with read capability.
     *
     * @return bool
     */
    public function shouldHandle() : bool
    {
        return InventoryIntegration::shouldLoad()
            && InventoryIntegration::hasCommerceCapability(Commerce::CAPABILITY_READ);
    }

    /**
     * @param ProductAssociation[] $productAssociations
     *
     * @return string[]
     */
    protected function getProductIds(array $productAssociations) : array
    {
        return array_values(
            array_filter(
                array_map(
                    static function (ProductAssociation $assoc) : ?string {
                        $inventory = $assoc->remoteResource->inventory;

                        // only attempt to prime caches for products that are actively using the inventory service
                        if ($inventory && $inventory->externalService && $inventory->tracking) {
                            return $assoc->remoteResource->productId;
                        }

                        return null;
                    },
                    $productAssociations
                )
            )
        );
    }
}
