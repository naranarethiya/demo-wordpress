<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\DataSources\WordPress\Adapters\TaxonomyTermAdapter;
use GoDaddy\WordPress\MWC\Common\Exceptions\AdapterException;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\Term;
use GoDaddy\WordPress\MWC\Common\Repositories\Exceptions\WordPressRepositoryException;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\TermsRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Backfill\Jobs\BackfillProductCategoriesJob;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\CategoryWritesInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\CategoryMapRepository;
use WP_Term;

/**
 * Adapts product category IDs (from an array of local IDs into an array of remote IDs, and vice versa).
 */
class ProductCategoriesAdapter implements DataSourceAdapterContract
{
    /** @var CategoryMapRepository category map repository */
    protected CategoryMapRepository $categoryMapRepository;

    public function __construct(CategoryMapRepository $categoryMapRepository)
    {
        $this->categoryMapRepository = $categoryMapRepository;
    }

    /**
     * Converts an array of remote category UUIDs into an array of local {@see Term} objects.
     *
     * @param string[]|null $remoteCategoryUuids
     * @return Term[]
     * @throws AdapterException|WordPressRepositoryException
     */
    public function convertFromSource(array $remoteCategoryUuids = null) : array
    {
        if (! is_array($remoteCategoryUuids)) {
            throw new AdapterException('Array of remote category UUIDs must be supplied.');
        }

        if (empty($remoteCategoryUuids)) {
            return [];
        }

        $mappings = $this->categoryMapRepository->getMappingsByRemoteIds($remoteCategoryUuids);
        $localIds = $mappings->getLocalIds();

        /*
         * If no local IDs are found, the remote categories do not exist locally.
         *
         * This occurs when the product is associated only with _new_ categories, and it is read from CH before
         * the categories are read.
         *
         * Proceeding would cause TermsRepository::getTerms() to return all terms and cause the product to be
         * associated with all existing local categories.
         **/
        if (empty($localIds)) {
            return [];
        }

        $wpTerms = TermsRepository::getTerms([
            'taxonomy'   => CatalogIntegration::PRODUCT_CATEGORY_TAXONOMY,
            'hide_empty' => false,
            'include'    => $localIds,
        ]);

        $this->maybeReportMismatchingCategoryCount($localIds, $remoteCategoryUuids);

        return $this->adaptTerms($wpTerms);
    }

    /**
     * Converts a product's local category associations into an array of remote category UUIDs.
     *
     * @param Term[]|null $localCategoryTerms
     * @return string[]
     * @throws AdapterException
     */
    public function convertToSource(array $localCategoryTerms = null) : array
    {
        if (! is_array($localCategoryTerms)) {
            throw new AdapterException('Array of local category terms must be supplied.');
        }

        $localCategoryTerms = TypeHelper::arrayOf($localCategoryTerms, Term::class, false);

        $localTermIds = array_filter(
            array_map(
                fn (Term $term) => $term->getId(),
                $localCategoryTerms
            )
        );

        if (empty($localTermIds)) {
            return [];
        }

        $remoteIds = $this->categoryMapRepository->getMappingsByLocalIds($localTermIds)->getRemoteIds();

        $this->maybeReportMismatchingCategoryCount($localTermIds, $remoteIds);

        return $remoteIds;
    }

    /**
     * Reports if we have a mismatching number local term IDs vs remote ones. This indicates some local categories
     * may not exist upstream.
     *
     * {@see CategoryWritesInterceptor} and {@see BackfillProductCategoriesJob} should have already created remote categories
     * for any local categories when the user creates new categories in the UI. Missing remote category IDs could indicate
     * an issue with category writes. This isn't expected to happen.
     *
     * @param int[] $localTermIds
     * @param string[] $remoteIds
     * @return void
     */
    protected function maybeReportMismatchingCategoryCount(array $localTermIds, array $remoteIds) : void
    {
        if (count($remoteIds) != count($localTermIds)) {
            SentryException::getNewInstance(sprintf(
                'Failed to convert all local category IDs to remote IDs. Local IDs: %s. Remote IDs: %s.',
                implode(', ', $localTermIds),
                implode(', ', $remoteIds)
            ));
        }
    }

    /**
     * Adapts WP_Terms into Terms.
     * @todo consider moving this to {@see TermsRepository}
     *
     * @param array<mixed> $wpTerms
     * @return Term[]
     */
    public function adaptTerms(array $wpTerms) : array
    {
        return array_map(
            fn (WP_Term $wpTerm) => TaxonomyTermAdapter::getNewInstance($wpTerm)->convertFromSource(),
            TypeHelper::arrayOf($wpTerms, WP_Term::class)
        );
    }
}
