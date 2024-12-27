<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce;

use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasWooCommerceObjectMetaTrait;
use GoDaddy\WordPress\MWC\Core\WordPress\Traits\HasNewObjectFlagMetaTrait;
use WC_Data;

/**
 * Represents a flag for an associated object.
 *
 * @since 2.10.0
 */
class NewWooCommerceObjectFlag
{
    use CanGetNewInstanceTrait;
    use HasNewObjectFlagMetaTrait;
    use HasWooCommerceObjectMetaTrait;

    /**
     * NewWooCommerceObjectFlag constructor.
     *
     * @param WC_Data $dataObject data object instance that owns the metadata
     */
    public function __construct(WC_Data $dataObject)
    {
        $this->wcDataObject = $dataObject;

        $this->loadMeta('no');
    }
}
