<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\Handlers\AbstractInterceptorHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Traits\CanInjectCommerceCategoriesIntoTermsArrayTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Categories\Category;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\WordPress\WpTerm;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters\CategoryWpTermAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\BatchListCategoriesByLocalIdService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Commerce;
use WP_Term;

/**
 * Handler for filtering {@see get_terms()} results.
 */
class TermQueryHandler extends AbstractInterceptorHandler
{
    use CanInjectCommerceCategoriesIntoTermsArrayTrait;

    /** @var CategoryWpTermAdapter adapter to convert {@see Category} DTOs into {@see WpTerm} objects */
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
     * Filters {@see get_terms()} results.
     *
     * @param ...$args
     * @return ?array<mixed>
     */
    public function run(...$args) : ?array
    {
        /** @var WP_Term[]|int[]|string[] $terms */
        $terms = ArrayHelper::get($args, 0);
        $taxonomies = ArrayHelper::get($args, 1);

        if (! is_array($terms) || ! is_array($taxonomies) || ! $this->shouldBuildTerms($terms, $taxonomies)) {
            return $terms;
        }

        $localIds = $this->buildLocalTermIds($terms);

        return ! empty($localIds) ? $this->injectCommerceData($terms, $localIds) : $terms;
    }

    /**
     * Builds an array of local term IDs that are relevant to product categories.
     *
     * @param WP_Term[]|int[]|string[] $terms
     * @return int[]
     */
    protected function buildLocalTermIds(array $terms) : array
    {
        $localIds = [];

        foreach ($terms as $term) {
            if ($term instanceof WP_Term && $term->taxonomy === CatalogIntegration::PRODUCT_CATEGORY_TAXONOMY) {
                $localIds[] = $term->term_id;
            } elseif (is_int($term)) {
                // we have to assume it's relevant, though we don't know for sure
                // if it's not a category, we'll find out later when it's not in our mapping table!
                $localIds[] = $term;
            }
        }

        return $localIds;
    }

    /**
     * Determines if the conditions are met to build terms.
     *
     * @param WP_Term[]|int[]|string[] $terms
     * @param string[] $taxonomies
     * @return bool
     */
    protected function shouldBuildTerms(array $terms, array $taxonomies) : bool
    {
        return ! empty($terms)
            && in_array(CatalogIntegration::PRODUCT_CATEGORY_TAXONOMY, $taxonomies, true)
            && CatalogIntegration::hasCommerceCapability(Commerce::CAPABILITY_READ);
    }
}
