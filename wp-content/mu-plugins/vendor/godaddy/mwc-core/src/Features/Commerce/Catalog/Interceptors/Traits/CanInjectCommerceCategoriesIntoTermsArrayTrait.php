<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Traits;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\AdapterException;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Categories\CategoryAssociation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\WordPress\WpTerm;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters\CategoryWpTermAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\BatchListCategoriesByLocalIdService;
use WP_Term;

/**
 * Trait for injecting Commerce data into the terms array.
 *
 * @property CategoryWpTermAdapter $wpTermAdapter
 */
trait CanInjectCommerceCategoriesIntoTermsArrayTrait
{
    /** @var BatchListCategoriesByLocalIdService */
    protected BatchListCategoriesByLocalIdService $batchListCategoriesByLocalIdService;

    /**
     * Executes an API request to fetch upstream categories, and injects the remote data into the local array of terms.
     *
     * @param WP_Term[]|int[]|string[]|object[] $terms
     * @param int[] $localIds
     * @return WP_Term[]|int[]|string[]|object[]
     */
    protected function injectCommerceData(array $terms, array $localIds) : array
    {
        try {
            $remoteCategories = $this->batchListCategoriesByLocalIdService->batchListByLocalIds($localIds);

            $terms = $this->modifyTermsArray($terms, $remoteCategories);
        } catch (Exception $e) {
            SentryException::getNewInstance($e->getMessage(), $e);
        }

        return $terms;
    }

    /**
     * Modifies the array of terms with injected Commerce data.
     *
     * @param WP_Term[]|int[]|string[]|object[] $terms
     * @param CategoryAssociation[] $remoteCategories
     * @return WP_Term[]|int[]|string[]|object[]
     * @throws AdapterException
     */
    protected function modifyTermsArray(array $terms, array $remoteCategories) : array
    {
        foreach ($terms as $categoryIndex => $localTerm) {
            if (! is_object($localTerm)) {
                continue;
            }

            $commerceCategory = $this->getRemoteCategory($localTerm, $remoteCategories);

            if (! $commerceCategory) {
                continue;
            }

            $terms[$categoryIndex] = $this->overlayCommerceDataOnTermObject($commerceCategory, $localTerm);
        }

        return $terms;
    }

    /**
     * Gets the remote category for the supplied local term.
     *
     * @param object $localTerm
     * @param CategoryAssociation[] $remoteCategories
     * @return WpTerm|null
     * @throws AdapterException
     */
    protected function getRemoteCategory(object $localTerm, array $remoteCategories) : ?WpTerm
    {
        if (
            empty($localTerm->term_id) ||
            empty($localTerm->taxonomy) ||
            CatalogIntegration::PRODUCT_CATEGORY_TAXONOMY !== $localTerm->taxonomy
        ) {
            return null;
        }

        /** @var CategoryAssociation $remoteCategory */
        $remoteCategory = ArrayHelper::where($remoteCategories, function (CategoryAssociation $categoryAssociation) use ($localTerm) {
            return $categoryAssociation->localId === (int) $localTerm->term_id;
        }, false);

        if (! isset($remoteCategory[0])) {
            return null;
        }

        return $this->wpTermAdapter->convertToSource($remoteCategory[0]->remoteResource);
    }

    /**
     * Overlays Commerce data onto the supplied local term object.
     *
     * @param WpTerm $commerceCategory
     * @param object $localTerm
     * @return object|WP_Term
     */
    protected function overlayCommerceDataOnTermObject(WpTerm $commerceCategory, object $localTerm) : object
    {
        if ($localTerm instanceof WP_Term) {
            return $commerceCategory->toWordPressTerm($localTerm);
        }

        return (object) $commerceCategory->toDatabaseArray((array) $localTerm);
    }
}
