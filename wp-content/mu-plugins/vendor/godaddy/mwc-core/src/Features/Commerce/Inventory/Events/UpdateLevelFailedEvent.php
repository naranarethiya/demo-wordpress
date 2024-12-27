<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Events;

use GoDaddy\WordPress\MWC\Common\Models\Products\Product;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

class UpdateLevelFailedEvent extends AbstractInventoryServiceFailEvent
{
    use CanGetNewInstanceTrait;

    /** @var Product|null */
    public ?Product $product;

    /**
     * Event constructor.
     *
     * @param Product|null $product
     * @param string $failReason
     */
    public function __construct(?Product $product, string $failReason)
    {
        $this->product = $product;
        $this->failReason = $failReason;
    }
}
