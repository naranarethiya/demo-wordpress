<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * An event that is fired when a product is created in the remote platform.
 *
 * @method static static getNewInstance(Product $product, string $remoteProductId, ProductBase $remoteProduct)
 */
class ProductCreatedEvent implements EventContract
{
    use CanGetNewInstanceTrait;

    /** @var Product local product */
    public Product $product;

    /** @var string ID of the product in the remote platform */
    public string $remoteProductId;

    /** @var ProductBase remote product from the API response */
    public ProductBase $remoteProduct;

    public function __construct(Product $product, string $remoteProductId, ProductBase $remoteProduct)
    {
        $this->product = $product;
        $this->remoteProductId = $remoteProductId;
        $this->remoteProduct = $remoteProduct;
    }
}
