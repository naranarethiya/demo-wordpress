<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\AdapterException;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\Handlers\AbstractInterceptorHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Exceptions\CategoryMappingNotFoundException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers\RemoteCategoryNotFoundHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\ReadCategoryOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Categories\Category;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters\CategoryWpTermAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\CategoriesServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Commerce;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\GatewayRequest404Exception;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Exceptions\CachingStrategyException;
use WP_Term;

/**
 * Interceptor handler for reading categories.
 */
class CategoryReadHandler extends AbstractInterceptorHandler
{
    protected CategoriesServiceContract $categoriesService;
    protected CategoryWpTermAdapter $categoryWpTermAdapter;
    protected RemoteCategoryNotFoundHelper $remoteCategoryNotFoundHelper;

    /**
     * Constructor.
     *
     * @param CategoriesServiceContract $categoriesService
     * @param CategoryWpTermAdapter $categoryWpTermAdapter
     * @param RemoteCategoryNotFoundHelper $remoteCategoryNotFoundHelper
     */
    public function __construct(
        CategoriesServiceContract $categoriesService,
        CategoryWpTermAdapter $categoryWpTermAdapter,
        RemoteCategoryNotFoundHelper $remoteCategoryNotFoundHelper
    ) {
        $this->categoriesService = $categoriesService;
        $this->categoryWpTermAdapter = $categoryWpTermAdapter;
        $this->remoteCategoryNotFoundHelper = $remoteCategoryNotFoundHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function run(...$args)
    {
        $term = ArrayHelper::get($args, 0);

        if (! $term instanceof WP_Term) {
            return $term;
        }

        try {
            if ($this->shouldReadTerm($term)) {
                return $this->buildOverlaidTermObject($term);
            }
        } catch(GatewayRequest404Exception $e) {
            // Indicates the category was deleted upstream. Delete the local category.
            if (! empty($term->term_id)) {
                $this->remoteCategoryNotFoundHelper->handle($term->term_id);
            }

            return false;
        } catch(CategoryMappingNotFoundException $e) {
            // Indicates the category has not been written to the platform yet. For now we do not need to report this.
            // Return the original object as-is.
        } catch(Exception|CommerceExceptionContract $e) {
            // We want to report all other errors to Sentry.
            SentryException::getNewInstance($e->getMessage(), $e);
        }

        return $term;
    }

    /**
     * Should read the term.
     *
     * @param WP_Term $term
     * @return bool
     */
    protected function shouldReadTerm(WP_Term $term) : bool
    {
        return CatalogIntegration::PRODUCT_CATEGORY_TAXONOMY === $term->taxonomy &&
            CatalogIntegration::hasCommerceCapability(Commerce::CAPABILITY_READ);
    }

    /**
     * Builds a new WP_Term-style object, with data overlaid from the platform.
     *
     *  This performs the following:
     *   - Executes an API request to get the platform-equivalent category of a local term ID {@see static::getCategoryFromLocalId()}
     *   - Adapts that {@see Category} instance into a {@see WpTerm} object.
     *   - Overlays our term data on top of the original {@see WP_Term} object, so that platform values override local ones.
     *   - Then returns the resulting object.
     *
     * @param WP_Term $term
     * @return WP_Term
     * @throws AdapterException|CommerceExceptionContract|CachingStrategyException|GatewayRequest404Exception|CategoryMappingNotFoundException
     */
    protected function buildOverlaidTermObject(WP_Term $term) : WP_Term
    {
        // API request to the platform to get a Category from a local ID
        $platformCategory = $this->getCategoryFromLocalId($term->term_id);

        // convert that Category into a WpTerm object
        $categoryWpTerm = $this->categoryWpTermAdapter->convertToSource($platformCategory);

        // overlay our WpTerm object data on top of the original WP_Term object
        return $categoryWpTerm->toWordPressTerm($term);
    }

    /**
     * Gets a Category DTO from a local term ID.
     *
     * @param int $termId
     * @return Category
     * @throws CommerceExceptionContract|CachingStrategyException|GatewayRequest404Exception|CategoryMappingNotFoundException
     */
    protected function getCategoryFromLocalId(int $termId) : Category
    {
        return $this->categoriesService
            ->readCategory(ReadCategoryOperation::seed(['localId' => $termId]))
            ->getCategory();
    }
}
