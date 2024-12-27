<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Http\Requests;

/**
 * API request to create a new draft listing on GDM.
 */
class CreateDraftListingRequest extends GoDaddyMarketplacesRequest
{
    /** @var string request route */
    protected $route = 'events';

    /** @var string channel UUID */
    protected $channelUuid;

    /** @var string SKU of the product we're creating a listing for */
    protected $productSku;

    /**
     * Gets the channel UUID.
     *
     * @return string
     */
    public function getChannelUuid() : string
    {
        return $this->channelUuid;
    }

    /**
     * Gets the product SKU.
     *
     * @return string
     */
    public function getProductSku() : string
    {
        return $this->productSku;
    }

    /**
     * Sets the channel UUID.
     *
     * @param string $value
     * @return $this
     */
    public function setChannelUuid(string $value) : CreateDraftListingRequest
    {
        $this->channelUuid = $value;

        return $this;
    }

    /**
     * Sets the product SKU.
     *
     * @param string $value
     * @return $this
     */
    public function setProductSku(string $value) : CreateDraftListingRequest
    {
        $this->productSku = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function send()
    {
        $this->setBody($this->buildBodyData());

        return parent::send();
    }

    /**
     * Builds the request body.
     *
     * @return array<string, mixed>
     */
    protected function buildBodyData() : array
    {
        return [
            'partner' => static::PARTNER,
            'event'   => [
                'event_name' => 'LISTING_CREATED',
                'event_data' => [
                    'channel_uuid' => $this->getChannelUuid(),
                    'data'         => [
                        [
                            'skus' => $this->getProductSku(),
                        ],
                    ],
                ],
            ],
        ];
    }
}
