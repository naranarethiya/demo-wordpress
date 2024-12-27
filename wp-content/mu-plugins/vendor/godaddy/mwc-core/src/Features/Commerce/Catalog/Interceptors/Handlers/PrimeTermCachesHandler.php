<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\Handlers\AbstractInterceptorHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\CategoryReadInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Traits\CanInjectCommerceCategoriesIntoTermsArrayTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters\CategoryWpTermAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\BatchListCategoriesByLocalIdService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Commerce;

/**
 * Handler for {@see CategoryReadInterceptor}.
 */
class PrimeTermCachesHandler extends AbstractInterceptorHandler
{
    use CanInjectCommerceCategoriesIntoTermsArrayTrait;

    /** @var CategoryWpTermAdapter term adapter */
    protected CategoryWpTermAdapter $wpTermAdapter;

    /**
     * Constructor.
     *
     * @param BatchListCategoriesByLocalIdService $batchListCategoriesByLocalIdService
     * @param CategoryWpTermAdapter $wpTermAdapter
     */
    public function __construct(BatchListCategoriesByLocalIdService $batchListCategoriesByLocalIdService, CategoryWpTermAdapter $wpTermAdapter)
    {
        $this->batchListCategoriesByLocalIdService = $batchListCategoriesByLocalIdService;
        $this->wpTermAdapter = $wpTermAdapter;
    }

    /**
     * Primes the terms cache.
     *
     * @param ...$args
     * @return array<mixed>|mixed
     */
    public function run(...$args)
    {
        /** @var object[] $terms array of standard objects from the {@see _prime_term_caches()} database query */
        $terms = TypeHelper::array($args[0] ?? [], []);
        /** @var int[] $localIds array of term IDs */
        $localIds = ArrayHelper::wrap($args[1] ?? []);

        if (empty($terms) || ! CatalogIntegration::hasCommerceCapability(Commerce::CAPABILITY_READ)) {
            return $terms;
        }

        // the array_map() here ensures that the pluck method will traverse the array of objects
        $taxonomies = ArrayHelper::pluck(array_map(function ($object) {
            return (array) $object;
        }, $terms), 'taxonomy');

        return ArrayHelper::contains($taxonomies, CatalogIntegration::PRODUCT_CATEGORY_TAXONOMY)
            ? $this->injectCommerceData($terms, $localIds)
            : $terms;
    }
}
