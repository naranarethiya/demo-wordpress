<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Listing;

/**
 * Adapts listing data from a GDM API response to a native core Listing object.
 */
class ListingAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var array Listing data from the API response */
    protected $source;

    /**
     * ListingAdapter constructor.
     *
     * @param array $listing Listing data from the API response.
     */
    public function __construct(array $listing)
    {
        $this->source = $listing;
    }

    /**
     * Converts a source listing to a native Listing object.
     *
     * @return Listing
     */
    public function convertFromSource() : Listing
    {
        return (new Listing())
            ->setLink(ArrayHelper::get($this->source, 'data.path', ''))
            ->setIsPublished(false);
    }

    /**
     * {@inheritDoc}
     */
    public function convertToSource()
    {
        // Not implemented.
        return [];
    }
}
