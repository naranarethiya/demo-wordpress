<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\Downloadable;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use WC_Product_Download;

/**
 * Converts between a native {@see Downloadable} object and a WooCommerce downloadable data array.
 *
 * @method static static getNewInstance(WC_Product_Download $downloadable)
 */
class DownloadableAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var WC_Product_Download */
    protected WC_Product_Download $source;

    /**
     * Constructor.
     *
     * @param WC_Product_Download $downloadable
     */
    public function __construct(WC_Product_Download $downloadable)
    {
        $this->source = $downloadable;
    }

    /**
     * Converts a source {@see WC_Product_Download} into a {@see Downloadable} object.
     *
     * @return Downloadable
     */
    public function convertFromSource() : Downloadable
    {
        return Downloadable::getNewInstance([
            'id'   => TypeHelper::string($this->source->get_id(), ''),
            'name' => TypeHelper::string($this->source->get_name(), ''),
            'url'  => TypeHelper::string($this->source->get_file(), ''),
        ]);
    }

    /**
     * Converts a native {@see Downloadable} object into a {@see WC_Product_Download} object.
     *
     * @param Downloadable|null $downloadable
     * @return WC_Product_Download|null
     */
    public function convertToSource(Downloadable $downloadable = null) : ?WC_Product_Download
    {
        if (! $downloadable) {
            return null;
        }

        $this->source->set_id($downloadable->getId());
        $this->source->set_name($downloadable->getName());
        $this->source->set_file($downloadable->getUrl());

        return $this->source;
    }
}
