<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Models\Downloadable;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\File;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * Adapter for converting a product's list of {@see Downloadable} objects to a list of {@see File} objects.
 */
class FilesAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /**
     * Converts {@see Downloadable} items from a {@see Product} model to a list of {@see File} DTOs.
     *
     * @param Product|null $product
     * @return ?File[]
     */
    public function convertToSource(Product $product = null) : ?array
    {
        if (! $product) {
            return null;
        }

        $downloadables = $product->getDownloadables();

        if (empty($downloadables)) {
            return null;
        }

        $files = [];

        foreach ($downloadables as $downloadable) {
            if ($file = $this->convertDownloadableToSource($downloadable)) {
                $files[] = $file;
            }
        }

        return ! empty($files) ? $files : null;
    }

    /**
     * Converts a {@see Downloadable} object to a {@see File} DTO.
     *
     * Returns null if any of the required fields are empty.
     *
     * @param Downloadable $downloadable
     * @return File|null
     */
    protected function convertDownloadableToSource(Downloadable $downloadable) : ?File
    {
        $name = $downloadable->getName();
        $label = $downloadable->getLabel();
        $url = $downloadable->getUrl();
        $id = $downloadable->getId();

        // sanity check: these should always have been adapted, but they can't be empty
        if (empty($id) || empty($url)) {
            return null;
        }

        return File::getNewInstance([
            'name'        => empty($name) ? $url : $name, // ensures a name is always set to a non-empty string
            'description' => empty($label) ? null : $label, // set to null rather than empty string
            'url'         => $url,
            'objectKey'   => $id,
        ]);
    }

    /**
     * @inerhitDoc
     */
    public function convertFromSource()
    {
        // no-op for now
    }
}
