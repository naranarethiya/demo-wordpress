<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Gateways;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Payments\Exceptions\InvalidSourceException;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Gateways\Traits\CanListResourcesTrait;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters\AbstractProductRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters\CreateProductRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters\GetProductRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters\ProductListRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters\UpdateProductRequestAdapter;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;
use GoDaddy\WordPress\MWC\Payments\Gateways\AbstractGateway;

/**
 * Products gateway.
 */
class ProductsGateway extends AbstractGateway
{
    use CanListResourcesTrait;
    use CanGetNewInstanceTrait;

    /**
     * Get a list of products.
     *
     * @param Product[] $selection
     * @return Product[]
     * @throws Exception
     */
    public function getList(array $selection = []) : array
    {
        /** @var Product[] $products */
        $products = TypeHelper::array($this->doAdaptedRequest($this, $this->getNewProductListRequestAdapter($this)), []);

        /* @phpstan-ignore-next-line */
        return TypeHelper::array(empty($selection) ? $products : array_filter($products, static function ($product) use ($selection) {
            return in_array($product->getRemoteId(), $selection, true);
        }), []);
    }

    /**
     * Get a single product by id.
     *
     * @param string $productId Product (remote) id.
     *
     * @return Product
     * @throws Exception
     */
    public function get(string $productId) : Product
    {
        return $this->doAdaptedRequest(
            $this,
            $this->getProductRequestAdapter(
                GetProductRequestAdapter::class,
                Product::getNewInstance()->setRemoteId($productId)
            )
        );
    }

    /**
     * Upsert a single product.
     *
     * @param Product $product
     *
     * @return Product
     * @throws Exception
     */
    public function upsert(Product $product) : Product
    {
        return $this->doAdaptedRequest(
            $this,
            $this->getProductRequestAdapter(
                $product->getRemoteId()
                    ? UpdateProductRequestAdapter::class
                    : CreateProductRequestAdapter::class, $product
            )
        );
    }

    /**
     * Gets a product request adapter class instance for the given Product.
     *
     * @param string $adapterClass The concrete request adapter class to return. Must be a subclass of AbstractProductRequestAdapter.
     * @param Product $product
     * @return AbstractProductRequestAdapter
     * @throws InvalidSourceException
     */
    protected function getProductRequestAdapter(string $adapterClass, Product $product) : AbstractProductRequestAdapter
    {
        if (! is_subclass_of($adapterClass, AbstractProductRequestAdapter::class)) {
            throw new InvalidSourceException('Product request adapter class must be a subclass of AbstractProductRequestAdapter');
        }

        return new $adapterClass($product);
    }

    /**
     * Gets a new ProductListRequestAdapter instance for the given ProductsGateway instance.
     *
     * @param ProductsGateway $gateway
     * @return ProductListRequestAdapter
     */
    protected function getNewProductListRequestAdapter(ProductsGateway $gateway) : ProductListRequestAdapter
    {
        return new ProductListRequestAdapter($gateway);
    }
}
