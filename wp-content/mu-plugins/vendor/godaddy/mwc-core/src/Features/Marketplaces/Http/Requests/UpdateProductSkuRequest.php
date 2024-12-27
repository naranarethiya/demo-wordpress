<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Http\Requests;

use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Exceptions\GoDaddyMarketplacesRequestException;

/**
 * Request to update the product SKU when the WooCommerce product SKU changes.
 */
class UpdateProductSkuRequest extends GoDaddyMarketplacesRequest
{
    /** @var string */
    protected $route = 'events';

    /** @var int|null */
    protected ?int $productId;

    /** @var string|null */
    protected ?string $oldSku;

    /** @var string|null */
    protected ?string $newSku;

    /**
     * Gets the product ID.
     *
     * @return int|null
     */
    public function getProductId() : ?int
    {
        return $this->productId;
    }

    /**
     * Sets the product ID.
     *
     * @param int $value
     * @return $this
     */
    public function setProductId(int $value) : UpdateProductSkuRequest
    {
        $this->productId = $value;

        return $this;
    }

    /**
     * Gets the old SKU.
     *
     * @return string|null
     */
    public function getOldSku() : ?string
    {
        return $this->oldSku;
    }

    /**
     * Sets the old SKU.
     *
     * @param string $value
     * @return $this
     */
    public function setOldSku(string $value) : UpdateProductSkuRequest
    {
        $this->oldSku = $value;

        return $this;
    }

    /**
     * Gets the new SKU.
     *
     * @return string|null
     */
    public function getNewSku() : ?string
    {
        return $this->newSku;
    }

    /**
     * Sets the new SKU.
     *
     * @param string $value
     * @return $this
     */
    public function setNewSku(string $value) : UpdateProductSkuRequest
    {
        $this->newSku = $value;

        return $this;
    }

    /**
     * Builds the request body.
     *
     * @return array<string, mixed>
     * @throws GoDaddyMarketplacesRequestException
     */
    protected function buildBodyData() : array
    {
        $oldSku = $this->getOldSku();
        $newSku = $this->getNewSku();

        if (empty($oldSku)) {
            throw new GoDaddyMarketplacesRequestException(sprintf('Cannot update a product SKU in Marketplaces if the product had an empty SKU (product ID #%s).', (int) $this->getProductId()));
        }
        if (empty($newSku)) {
            throw new GoDaddyMarketplacesRequestException(sprintf('Cannot update a product SKU in Marketplaces to be empty (product ID #%s).', (int) $this->getProductId()));
        }

        return [
            'partner' => static::PARTNER,
            'event'   => [
                'event_name' => 'SKU_UPDATED',
                'event_data' => [
                    'sku' => [
                        'old_value' => $oldSku,
                        'new_value' => $newSku,
                    ],
                ],
            ],
        ];
    }
}
