<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events;

use GoDaddy\WordPress\MWC\Common\Events\ModelEvent;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Listing;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * Event for when a listing has been created in GoDaddy Marketplaces.
 */
class ListingCreatedEvent extends ModelEvent
{
    /** @var Product product the listing was created for */
    protected Product $product;

    /**
     * Listing created model event constructor.
     *
     * @param Listing $model
     * @param Product $product
     */
    public function __construct(Listing $model, Product $product)
    {
        parent::__construct($model, 'marketplace_listing', 'create');

        $this->product = $product;
    }

    /**
     * Gets the product the listing was created for.
     *
     * @return Product
     */
    public function getProduct() : Product
    {
        return $this->product;
    }
}
