<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events;

use GoDaddy\WordPress\MWC\Common\Events\ModelEvent;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Listing;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * Event for when a listing has been deleted on GoDaddy Marketplaces.
 */
class ListingDeletedEvent extends ModelEvent
{
    /** @var Product associated WooCommerce product */
    protected Product $product;

    /**
     * Listing deleted model event constructor.
     *
     * @param Listing $model
     * @param Product $product
     */
    public function __construct(Listing $model, Product $product)
    {
        parent::__construct($model, 'marketplace_listing', 'delete');

        $this->product = $product;
    }

    /**
     * Gets the associated WooCommerce product.
     *
     * @return Product
     */
    public function getProduct() : Product
    {
        return $this->product;
    }
}
