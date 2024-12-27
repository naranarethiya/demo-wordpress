<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Repositories;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Listing;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

class ListingRepository
{
    /**
     * Gets the product listing(s) by channel.
     *
     * @param Product $product
     * @param string $channelTypeSlug
     * @param bool $onlyPublished
     * @return Listing[]
     */
    public static function getProductListingsByChannelType(Product $product, string $channelTypeSlug, bool $onlyPublished = false) : array
    {
        return ArrayHelper::where($product->getMarketplacesListings(), function ($listing) use ($channelTypeSlug, $onlyPublished) {
            /* @var Listing $listing */
            return (! $onlyPublished || $listing->isPublished())
                   && $channelTypeSlug === $listing->getChannelType();
        }, false);
    }
}
