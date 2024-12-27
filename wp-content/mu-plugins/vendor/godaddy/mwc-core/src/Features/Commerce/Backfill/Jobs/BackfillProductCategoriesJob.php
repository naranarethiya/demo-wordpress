<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Backfill\Jobs;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\WordPressDatabaseException;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\TermsRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\DataStores\CategoryDataStore;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers\CategoryEligibilityHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\CategoryMapRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\SkippedResources\SkippedCategoriesRepository;
use WP_Term;

/**
 * Backfill product categories job class.
 */
class BackfillProductCategoriesJob extends AbstractBackfillResourceJob
{
    /** @var string unique identifier for the queue.jobs config */
    public const JOB_KEY = 'backfillProductCategories';

    /** @var CategoryMapRepository category map repository */
    protected CategoryMapRepository $categoryMapRepository;

    /** @var CategoryDataStore categories data store */
    protected CategoryDataStore $categoryDataStore;

    public function __construct(CategoryMapRepository $categoryMapRepository, CategoryDataStore $categoryDataStore, SkippedCategoriesRepository $skippedCategoriesRepository)
    {
        $this->categoryMapRepository = $categoryMapRepository;
        $this->categoryDataStore = $categoryDataStore;

        parent::__construct($skippedCategoriesRepository);
    }

    /**
     * Queries for the local term objects.
     *
     * @return WP_Term[]|null
     */
    protected function getLocalResources() : ?array
    {
        $localIds = $this->categoryMapRepository->getUnmappedLocalIds(
            $this->getJobSettings()->maxPerBatch
        );

        if (empty($localIds)) {
            return null;
        }

        /** @var WP_Term[] $terms */
        $terms = CatalogIntegration::withoutReads(function () use ($localIds) {
            return TermsRepository::getTerms([
                'include'    => $localIds,
                'taxonomy'   => CatalogIntegration::PRODUCT_CATEGORY_TAXONOMY,
                'hide_empty' => false,
            ]);
        });

        $this->setAttemptedResourcesCount(count($terms));

        return $terms;
    }

    /**
     * Creates a resource in the platform if it's eligible. Logs ineligible and failed items.
     *
     * @param WP_Term $resource
     * @return void
     * @throws CommerceExceptionContract|WordPressDatabaseException
     */
    protected function maybeCreateResourceInPlatform($resource) : void
    {
        try {
            if (! CategoryEligibilityHelper::shouldWriteCategoryToPlatform($resource)) {
                throw new Exception('Category not eligible.');
            }

            $this->categoryDataStore->createOrUpdateCategoryInPlatform($resource);
        } catch(Exception|CommerceExceptionContract $exception) {
            $this->markLocalResourceAsSkipped($resource->term_id);
        }
    }
}
