<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Gateways;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Gateways\Traits\CanListResourcesTrait;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters\AddProductsToCatalogRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters\CatalogListRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters\CatalogRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\CatalogRequest;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\GetCatalogRequest;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Models\Catalog;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;
use GoDaddy\WordPress\MWC\Payments\Gateways\AbstractGateway;

/**
 * Catalogs gateway.
 */
class CatalogsGateway extends AbstractGateway
{
    use CanListResourcesTrait;
    use CanGetNewInstanceTrait;

    /**
     * Get a list of catalogs.
     *
     * @param array $selection
     * @return array
     * @throws Exception
     */
    public function getList(array $selection = []) : array
    {
        $catalogs = TypeHelper::array($this->doAdaptedRequest($this, new CatalogListRequestAdapter($this)), []);

        return TypeHelper::array(empty($selection) ? $catalogs : array_filter($catalogs, static function ($catalog) use ($selection) {
            /* @var Catalog $catalog */
            return in_array($catalog->getRemoteId(), $selection, true);
        }), []);
    }

    /**
     * Get a single catalog by id.
     *
     * @param string $catalogId
     * @param bool $getFull
     * @return Catalog
     * @throws Exception
     */
    public function get(string $catalogId, bool $getFull = true) : Catalog
    {
        $response = GetCatalogRequest::getNewInstance($catalogId, $getFull)->send();

        $adapter = CatalogRequestAdapter::getNewInstance(Catalog::getNewInstance());

        return $adapter->convertToSource($response);
    }

    /**
     * Update a single catalog.
     *
     * @param string $id
     * @param array $data
     */
    public function update(string $id, array $data)
    {
        // @TODO Need to implement {ssmith1 2022-1-26}
    }

    /**
     * Adds one or more products to a catalog.
     *
     * @param string $catalogId
     * @param Product[] $products
     * @throws Exception
     */
    public function addProducts(string $catalogId, array $products)
    {
        if (empty($products)) {
            return;
        }

        $catalog = $this->get($catalogId, false);

        if (empty($productsNotInCatalog = $this->getProductsNotInCatalog($products, $catalog))) {
            return;
        }

        // TODO: consider converting the response back to the Catalog object and returning it {@itambek 2022-02-11}
        $this->doRequest(AddProductsToCatalogRequestAdapter::getNewInstance($catalog)->convertFromSource($productsNotInCatalog));
    }

    /**
     * Removes one or more products from a catalog.
     *
     * @param string $catalogId
     * @param array $products
     * @throws Exception
     */
    public function removeProducts(string $catalogId, array $products)
    {
        // TODO: how should we handle cases where a product does not exist in the remote catalog? Currently, they are silently ignored {@itambek 2022-02-4}
        $this->doRequest(CatalogRequest::getNewInstance($catalogId)
            ->setMethod('PATCH')
            ->setBody(
                array_map(static function ($index) {
                    return [
                        'op'   => 'remove',
                        'path' => "/products/{$index}",
                    ];
                }, $this->getProductIndexesInCatalog($products, $this->get($catalogId, false)))
            ));
    }

    /**
     * Gets a list of the given products not in the given catalog.
     *
     * @param array $products
     * @param Catalog $catalog
     * @return Product[]
     */
    protected function getProductsNotInCatalog(array $products, Catalog $catalog) : array
    {
        return array_values(array_filter($products, function ($product) use ($catalog) {
            return is_null($this->getProductIndexInCatalog($product, $catalog));
        }));
    }

    /**
     * Determines indexes of given products in the catalog.
     *
     * @param Product[] $products
     * @param Catalog $catalog
     * @return int[]
     */
    protected function getProductIndexesInCatalog(array $products, Catalog $catalog) : array
    {
        return array_values(array_filter(array_map(function ($product) use ($catalog) {
            return $this->getProductIndexInCatalog($product, $catalog);
        }, $products), 'is_int'));
    }

    /**
     * Determines index of the given product in the catalog.
     *
     * @param Product $product
     * @param Catalog $catalog
     * @return int|null
     */
    protected function getProductIndexInCatalog(Product $product, Catalog $catalog) : ?int
    {
        foreach ($catalog->getProducts() as $index => $catalogProduct) {
            if ($catalogProduct->getRemoteId() === $product->getRemoteId()) {
                return (int) $index;
            }
        }

        return null;
    }
}
