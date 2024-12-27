<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Events;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventBridgeEventContract;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;
use GoDaddy\WordPress\MWC\Common\Traits\IsEventBridgeEventTrait;
use GoDaddy\WordPress\MWC\Core\Payments\Models\StoreDevice;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt;

/**
 * A temporary one-time event to broadcast products that have been synced. @see MWC-12044.
 */
class BroadcastSyncedProductsEvent implements EventBridgeEventContract
{
    use IsEventBridgeEventTrait;

    /** @var array<array<string, string|int>> array of product information, including ID and SKU */
    protected array $productData;

    /**
     * Constructor.
     *
     * @param array<array<string, string|int>> $productData
     */
    public function __construct(array $productData)
    {
        $this->action = 'gdp_products_broadcast';
        $this->productData = $productData;
    }

    /**
     * Builds the event data.
     *
     * @return array<string, mixed>
     * @throws Exception
     */
    protected function buildInitialData() : array
    {
        /** @var StoreDevice[] $devices */
        $devices = array_filter(Poynt::getStoreDevices(), fn (StoreDevice $device) => ! empty($device->getId()));

        return [
            'wooSiteStoreId'   => PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->getStoreRepository()->getStoreId(),
            'poyntSiteStoreId' => Poynt::getSiteStoreId(),
            'devices'          => array_values(array_map(fn (StoreDevice $device) => $device->toArray(), $devices)),
            'products'         => $this->productData,
        ];
    }
}
