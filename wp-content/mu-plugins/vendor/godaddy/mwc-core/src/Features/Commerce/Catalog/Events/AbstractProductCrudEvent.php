<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductAssociation;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * An event that is fired when a product crud operation occurs.
 *
 * @method static static getNewInstance($productAssociations)
 */
abstract class AbstractProductCrudEvent implements EventContract
{
    use CanGetNewInstanceTrait;

    /** @var ProductAssociation[] */
    public array $productAssociations;

    /**
     * Constructs the event.
     *
     * @param ProductAssociation|ProductAssociation[] $productAssociations
     */
    public function __construct($productAssociations)
    {
        $this->productAssociations = ArrayHelper::wrap($productAssociations);
    }
}
