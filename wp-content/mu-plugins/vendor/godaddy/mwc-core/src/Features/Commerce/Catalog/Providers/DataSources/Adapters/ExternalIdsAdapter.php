<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\ExternalId;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * Adapter for converting a {@see Product} external IDs to a corresponding array of {@see ExternalId} DTO objects.
 */
class ExternalIdsAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /**
     * Converts a {@see Product} external IDs into an array of {@see ExternalId} DTOs.
     *
     * @param Product|null $product
     * @return ExternalId[]
     */
    public function convertToSource(Product $product = null) : array
    {
        $externalIds = [];

        if (! $product) {
            return $externalIds;
        }

        if ($gtin = $product->getMarketplacesGtin()) {
            $externalIds[] = new ExternalId([
                'type'  => ExternalId::TYPE_GTIN,
                'value' => $gtin,
            ]);
        }

        if ($mpn = $product->getMarketplacesMpn()) {
            $externalIds[] = new ExternalId([
                'type'  => ExternalId::TYPE_MPN,
                'value' => $mpn,
            ]);
        }

        if ($globalUniqueId = $product->getGlobalUniqueId()) {
            $externalIds[] = new ExternalId([
                'type'  => ExternalId::TYPE_UPC,
                'value' => $globalUniqueId,
            ]);
        }

        return $externalIds;
    }

    /**
     * @inerhitDoc
     */
    public function convertFromSource()
    {
        // no-op for now
    }

    /**
     * Converts an array of external ID data into an array of {@see ExternalId} DTOs.
     *
     * @param array<array<string, string>> $externalIdsData
     * @return ExternalId[]|null
     */
    public function convertToSourceFromArray(array $externalIdsData) : ?array
    {
        $externalIds = [];

        foreach ($externalIdsData as $externalId) {
            $type = TypeHelper::string(ArrayHelper::get($externalId, 'type'), '');
            $value = TypeHelper::string(ArrayHelper::get($externalId, 'value'), '');

            if (empty($type) || empty($value)) {
                continue;
            }

            $externalIds[] = new ExternalId([
                'type'  => $type,
                'value' => $value,
            ]);
        }

        return $externalIds ?: null;
    }
}
