<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Http\Requests;

/**
 * Request to exchange WooCommerce product IDs for Google product IDs.
 */
class GetGoogleProductIdsRequest extends GoDaddyMarketplacesRequest
{
    /** @var string request route */
    protected $route = 'events';

    /** @var string[] WooCommerce product SKUs */
    protected array $productSkus = [];

    /**
     * Gets the WooCommerce product SKUs.
     *
     * @return string[]
     */
    public function getProductSkus() : array
    {
        return $this->productSkus;
    }

    /**
     * Sets the WooCommerce product SKUs.
     *
     * @param string[] $value
     * @return $this
     */
    public function setProductSkus(array $value) : GetGoogleProductIdsRequest
    {
        $this->productSkus = $value;

        return $this;
    }

    /**
     * Builds the request body.
     *
     * @return array<string, mixed>
     */
    public function buildBodyData() : array
    {
        return [
            'partner' => static::PARTNER,
            'event'   => [
                'event_name' => 'GOOGLE_PRODUCT_IDS',
                'event_data' => [
                    'skus' => $this->getProductSkus(),
                ],
            ],
        ];
    }
}
