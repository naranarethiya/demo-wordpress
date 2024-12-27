<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\DataStores\WooCommerce;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\ProductsRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Payments\DataStores\Contracts\DataStoreContract;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\ProductAdapter;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;
use WC_Product;
use WC_Product_Attribute;

/**
 * ProductDataStore class.
 */
class ProductDataStore implements DataStoreContract
{
    use CanGetNewInstanceTrait;

    /** @var string|null data provider name */
    protected $providerName;

    /** @var string[] the properties that should be converted to & from WooCommerce product meta values */
    protected $propertiesForMeta = [
        'remoteId',
        'remoteParentId',
        'source',
    ];

    /**
     * ProductDataStore constructor.
     *
     * @param string $providerName
     */
    public function __construct(string $providerName)
    {
        $this->providerName = $providerName;
    }

    /**
     * Reads product from the data store.
     *
     * @param int $id
     *
     * @return Product|null
     * @throws Exception
     */
    public function read(int $id = 0)
    {
        if (! $wooProduct = ProductsRepository::get($id)) {
            return null;
        }

        /** @var Product $product */
        $product = $this->getAdapter($wooProduct)->convertFromSource();

        $product->setSku($wooProduct->get_sku()); // TODO: remove once the ProductAdapter converts SKU itself (MWC-4291) {@cwiseman 2022-02-04}

        $product = $this->readAttributes($product, $wooProduct);

        return $this->readMeta($product, $wooProduct);
    }

    /**
     * Reads the attributes from the given WooCommerce product.
     *
     * @param Product $product
     * @param WC_Product $wooProduct
     * @return Product
     */
    protected function readAttributes(Product $product, WC_Product $wooProduct) : Product
    {
        $attributes = [];

        /** @var WC_Product_Attribute $attribute */
        foreach ($wooProduct->get_attributes() as $attribute) {
            if (! $attribute instanceof WC_Product_Attribute) {
                /*
                 * When `get_attributes()` is called on `WC_Product_Variation`, it returns array<string|int, mixed>
                 * instead of WC_Product_Attribute[]. This code doesn't yet support variants.
                 */
                continue;
            }

            // only include attributes that are visible on the product pages
            if (! $attribute->get_visible()) {
                continue;
            }

            // pull taxonomy values if this is a taxonomy attribute
            if ($attribute->is_taxonomy()) {
                $taxonomy = $attribute->get_taxonomy_object();
                $name = $taxonomy->attribute_label;

                $options = wc_get_product_terms(
                    $wooProduct->get_id(),
                    $attribute->get_name(),
                    ['fields' => 'names']
                );
            } else {
                $name = $attribute->get_name();
                $options = $attribute->get_options();
            }

            $attributes[$name] = [
                'options'     => $options,
                'cardinality' => 1,
            ];
        }

        return $product->setAttributeData($attributes);
    }

    /**
     * Reads the meta from the given Woo product and applies it to the core product.
     *
     * @param Product $product
     * @param WC_Product $wooProduct
     * @return Product
     */
    protected function readMeta(Product $product, WC_Product $wooProduct) : Product
    {
        foreach ($this->propertiesForMeta as $property) {
            $propertyMethod = 'set'.ucfirst($property);
            $metaKey = "mwp_{$this->providerName}_{$property}";

            if ($metaValue = (string) $wooProduct->get_meta($metaKey)) {
                $product->{$propertyMethod}($metaValue);
            }
        }

        return $product;
    }

    /**
     * Reads a product with the given remote ID.
     *
     * The first matching product will be returned.
     *
     * @param string $remoteId
     *
     * @return Product|null
     * @throws Exception
     */
    public function readFromRemoteId(string $remoteId)
    {
        $filter = Register::filter()
            ->setGroup('woocommerce_product_data_store_cpt_get_products_query')
            ->setHandler([$this, 'filterProductQuery'])
            ->setArgumentsCount(2);

        // apply the filter to this query
        $filter->execute();

        // TODO: replace with the ProductsRepository::query() method when it exists {@cwiseman 2022-02-07}
        $results = wc_get_products([
            'mwp' => [
                'providerName' => $this->providerName,
                'remoteId'     => $remoteId,
            ],
            'return' => 'ids',
        ]);

        // remove the filter from subsequent queries
        $filter->deregister();

        // the result can either be an object or array
        $firstFound = is_object($results) ? current($results->products) : current($results);

        return $this->read($firstFound);
    }

    /**
     * Adds meta query values to the given product query.
     *
     * @internal
     *
     * @param mixed $queryVars
     * @param mixed $customVars
     * @return mixed
     */
    public function filterProductQuery($queryVars, $customVars)
    {
        $providerName = ArrayHelper::get($customVars, 'mwp.providerName');
        $remoteId = ArrayHelper::get($customVars, 'mwp.remoteId');

        // add the query even if remote ID is empty to prevent Woo querying all products if the passed remote ID is invalid
        if (is_string($providerName) && ArrayHelper::accessible($queryVars)) {
            $queryVars['meta_query'][] = [
                'key'     => "mwp_{$providerName}_remoteId",
                'value'   => is_string($remoteId) ? $remoteId : '',
                'compare' => '=',
            ];
        }

        return $queryVars;
    }

    /**
     * Saves product to the data store.
     *
     * @param Product|null $product
     *
     * @return Product|null
     * @throws Exception
     */
    public function save(Product $product = null)
    {
        if (! $product) {
            return null;
        }

        $wooProduct = $this->getAdapter($this->getWooProductForSave((int) $product->getId()))
            ->convertToSource($product, false);

        $wooProduct->set_sku($product->getSku()); // TODO: remove once the ProductAdapter converts SKU itself (MWC-4291) {@cwiseman 2022-02-09}

        $wooProduct = $this->saveAttributes($wooProduct, $product->getAttributeData());
        $wooProduct = $this->saveMeta($wooProduct, $product);

        $wooProduct->save();

        return $product;
    }

    /**
     * Gets a Woo product instance ready for saving.
     *
     * @param int $id
     * @return WC_Product
     */
    protected function getWooProductForSave(int $id) : WC_Product
    {
        return ProductsRepository::get($id) ?? $this->getNewWooProduct();
    }

    /**
     * Gets a brand-new Woo product instance.
     *
     * @return WC_Product
     */
    protected function getNewWooProduct() : WC_Product
    {
        return new WC_Product();
    }

    /**
     * Saves the given attributes to the WooCommerce product.
     *
     * @param WC_Product $wooProduct
     * @param array $attributeData
     * @return WC_Product
     */
    protected function saveAttributes(WC_Product $wooProduct, array $attributeData) : WC_Product
    {
        $persistedAttributes = [];

        foreach ($attributeData as $attributeName => $rawAttribute) {
            // Woo only supports "exactly one" modifiers right now
            if (1 !== ArrayHelper::get($attributeData, 'cardinality', 1)) {
                continue;
            }

            $wooAttribute = $this->getNewProductAttribute();
            $wooAttribute->set_name($attributeName);
            $wooAttribute->set_options(ArrayHelper::get($rawAttribute, 'options', []));
            $wooAttribute->set_visible(true);

            $persistedAttributes[] = $wooAttribute;
        }

        $wooProduct->set_attributes($persistedAttributes);

        return $wooProduct;
    }

    /**
     * Gets a new product attribute instance.
     *
     * @return WC_Product_Attribute
     */
    protected function getNewProductAttribute() : WC_Product_Attribute
    {
        return new WC_Product_Attribute();
    }

    /**
     * Saves the properties from the given core product to the Woo product meta.
     *
     * @param WC_Product $wooProduct
     * @param Product $product
     * @return WC_Product
     */
    protected function saveMeta(WC_Product $wooProduct, Product $product) : WC_Product
    {
        foreach ($this->propertiesForMeta as $property) {
            $propertyMethod = 'get'.ucfirst($property);
            $metaKey = "mwp_{$this->providerName}_{$property}";

            if ($metaValue = $product->{$propertyMethod}()) {
                $wooProduct->update_meta_data($metaKey, $metaValue);
            }
        }

        return $wooProduct;
    }

    /**
     * Fulfills delete method from datastore.
     *
     * @return bool
     */
    public function delete() : bool
    {
        return false;
    }

    /**
     * Gets a Woo product adapter instance.
     *
     * @param WC_Product $wooProduct
     * @return ProductAdapter
     */
    protected function getAdapter(WC_Product $wooProduct) : ProductAdapter
    {
        return new ProductAdapter($wooProduct);
    }
}
