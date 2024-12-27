<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Events;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Models\Products\Product;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

class UpdateProductQuantityConflictEvent implements EventContract
{
    use CanGetNewInstanceTrait;

    /** @var float */
    public float $localQuantityBeforeUpdate;

    /** @var float */
    public float $remoteQuantityBeforeUpdate;

    /** @var float */
    public float $inputQuantity;

    /** @var float */
    public float $resolvedQuantity;

    /** @var Product */
    public Product $product;

    /**
     * Event constructor.
     *
     * @param float $localQuantityBeforeUpdate
     * @param float $remoteQuantityBeforeUpdate
     * @param float $inputQuantity
     * @param float $resolvedQuantity
     * @param Product $product
     */
    public function __construct(float $localQuantityBeforeUpdate, float $remoteQuantityBeforeUpdate, float $inputQuantity, float $resolvedQuantity, Product $product)
    {
        $this->localQuantityBeforeUpdate = $localQuantityBeforeUpdate;
        $this->remoteQuantityBeforeUpdate = $remoteQuantityBeforeUpdate;
        $this->inputQuantity = $inputQuantity;
        $this->resolvedQuantity = $resolvedQuantity;
        $this->product = $product;
    }
}
