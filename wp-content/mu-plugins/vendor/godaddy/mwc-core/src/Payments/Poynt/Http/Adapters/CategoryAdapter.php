<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Models\Category;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * An adapter for converting the core category object to and from Poynt API data.
 */
class CategoryAdapter implements DataSourceAdapterContract
{
    /** @var Category|null */
    protected $source;

    /**
     * Constructs the adapter.
     *
     * @param Category $category
     */
    public function __construct(Category $category)
    {
        $this->source = $category;
    }

    /**
     * Converts the source category to Poynt API data.
     *
     * @return array
     */
    public function convertFromSource() : array
    {
        $data = [
            'name' => $this->source->getName(),
        ];

        if ($products = $this->source->getProducts()) {
            $data['products'] = array_map(function ($product) {
                return $this->getNewProductAdapter($product)->convertFromSource();
            }, $products);
        }

        return $data;
    }

    /**
     * Applies Poynt API data to the source category object.
     *
     * @param array|null $data
     *
     * @return Category
     */
    public function convertToSource(array $data = []) : Category
    {
        if ($name = ArrayHelper::get($data, 'name')) {
            $this->source->setName($name);
        }

        if ($products = ArrayHelper::get($data, 'products')) {
            $this->source->setProducts(array_map(function ($product) {
                return $this->getNewProductAdapter()->convertToSource(ArrayHelper::get($product, 'product') ?? $product); // actual product data will be nested in a product field for some responses
            }, $products));
        }

        return $this->source;
    }

    /**
     * Gets a new product adapter instance.
     *
     * @param Product|null $product
     * @return ProductAdapter
     */
    protected function getNewProductAdapter(Product $product = null) : ProductAdapter
    {
        return new ProductAdapter($product ?? new Product());
    }
}
