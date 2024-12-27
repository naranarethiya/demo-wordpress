<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters;

use GoDaddy\WordPress\MWC\Core\Payments\Exceptions\InvalidProductException;
use GoDaddy\WordPress\MWC\Core\Payments\Exceptions\MissingRemoteIdException;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\CatalogRequest;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Models\Catalog;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;
use ReflectionException;

/**
 * An adapter for converting the core product gateway to and from Poynt API data.
 */
class AddProductsToCatalogRequestAdapter extends CatalogRequestAdapter
{
    /**
     * Converts the source catalog and products array to Poynt API catalog update request.
     *
     * @param array $products
     * @return CatalogRequest
     * @throws ReflectionException|MissingRemoteIdException|InvalidProductException
     */
    public function convertFromSource(array $products = []) : CatalogRequest
    {
        if (! $catalogId = $this->source->getRemoteId()) {
            throw new MissingRemoteIdException('The source catalog must have a remote ID');
        }

        if (empty($products)) {
            throw new InvalidProductException('A non-empty products parameter must be provided');
        }

        return CatalogRequest::getNewInstance($catalogId)
            ->setMethod('PATCH')
            ->setBody($this->getRequestBody($products));
    }

    /**
     * Gets the request body.
     *
     * @param Product[] $products
     * @return array[]
     */
    protected function getRequestBody(array $products) : array
    {
        // If the remote catalog has no products, we can use the `add` operation on the `/products` path, regardless if the remote
        // catalog has an empty `/products` path or is missing it altogether, as the `add` operation performs an upsert.
        if (empty($this->source->getProducts())) {
            // Note that the nested array here is intentional, as the body should be an array of json patch operations
            return [[
                'op'    => 'add',
                'path'  => '/products',
                'value' => array_map(static function (Product $product) {
                    return [
                        'id' => $product->getRemoteId(),
                    ];
                }, $products),
            ]];
        }

        return array_map(static function (Product $product) {
            return [
                'op'    => 'add',
                'path'  => '/products/-',
                'value' => [
                    'id' => $product->getRemoteId(),
                ],
            ];
        }, $products);
    }
}
