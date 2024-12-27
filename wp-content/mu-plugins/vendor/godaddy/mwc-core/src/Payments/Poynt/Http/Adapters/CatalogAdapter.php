<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Models\Catalog;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Models\Category;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * An adapter for converting the core category object to and from Poynt API data.
 */
class CatalogAdapter implements DataSourceAdapterContract
{
    /** @var Catalog|null */
    protected $source;

    /**
     * Constructs the adapter.
     *
     * @param Catalog $catalog
     */
    public function __construct(Catalog $catalog)
    {
        $this->source = $catalog;
    }

    /**
     * Converts the source catalog to Poynt API data.
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

        if ($categories = $this->source->getCategories()) {
            $data['categories'] = array_map(function ($category) {
                return $this->getNewCategoryAdapter($category)->convertFromSource();
            }, $categories);
        }

        return $data;
    }

    /**
     * Applies Poynt API data to the source catalog object.
     *
     * @param array|null $data
     *
     * @return Catalog
     */
    public function convertToSource(array $data = []) : Catalog
    {
        if ($remoteId = ArrayHelper::get($data, 'id')) {
            $this->source->setRemoteId($remoteId);
        }

        if ($remoteParentId = ArrayHelper::get($data, 'businessId')) {
            $this->source->setRemoteParentId($remoteParentId);
        }

        if ($name = ArrayHelper::get($data, 'name')) {
            $this->source->setName($name);
        }

        if ($products = ArrayHelper::get($data, 'products')) {
            $this->source->setProducts(array_map(function ($product) {
                return $this->getNewProductAdapter()->convertToSource(ArrayHelper::get($product, 'product') ?? $product); // actual product data will be nested in a product field for some responses
            }, $products));
        }

        if ($categories = ArrayHelper::get($data, 'categories')) {
            $this->source->setCategories(array_map(function ($category) {
                return $this->getNewCategoryAdapter()->convertToSource($category);
            }, $categories));
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

    /*
     * Gets a new category adapter instance.
     *
     * @param Category|null $product
     * @return ProductAdapter
     */
    protected function getNewCategoryAdapter(Category $category = null) : CategoryAdapter
    {
        return new CategoryAdapter($category ?? new Category());
    }
}
