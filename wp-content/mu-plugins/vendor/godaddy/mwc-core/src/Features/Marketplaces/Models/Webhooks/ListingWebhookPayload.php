<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Webhooks;

use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Listing;

/**
 * Object representing all the information we expect to receive from a listing webhook payload.
 */
class ListingWebhookPayload extends AbstractWebhookPayload
{
    /** @var Listing|null */
    protected $listing;

    /** @var int|null */
    protected $productId;

    /**
     * Gets the listing.
     *
     * @return Listing
     */
    public function getListing() : ?Listing
    {
        return $this->listing;
    }

    /**
     * Gets the WooCommerce product ID.
     *
     * @return int|null
     */
    public function getProductId() : ?int
    {
        return $this->productId;
    }

    /**
     * Sets the listing.
     *
     * @param Listing|null $value
     * @return $this
     */
    public function setListing(?Listing $value) : ListingWebhookPayload
    {
        $this->listing = $value;

        return $this;
    }

    /**
     * Sets the WooCommerce product ID.
     *
     * @param int|null $value
     * @return $this
     */
    public function setProductId(?int $value) : ListingWebhookPayload
    {
        $this->productId = $value;

        return $this;
    }
}
