<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\Handlers\AbstractInterceptorHandler;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\TermsRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\DataStores\CategoryDataStore;
use WP_Term;

/**
 * Handler for the {@see CategoryWritesInterceptor}.
 */
class CategoryWritesHandler extends AbstractInterceptorHandler
{
    protected CategoryDataStore $categoryDataStore;

    public function __construct(CategoryDataStore $categoryDataStore)
    {
        $this->categoryDataStore = $categoryDataStore;
    }

    /**
     * Creates or updates a product category in the service after one is saved locally.
     *
     * @param ...$args
     * @return void
     */
    public function run(...$args)
    {
        $termId = TypeHelper::int(ArrayHelper::get($args, 0), 0);

        if (! $wpTerm = $this->getWordPressTermObject($termId)) {
            return;
        }

        // @NOTE: the write capability is checked in the below method
        $this->categoryDataStore->createOrUpdate($wpTerm);
    }

    /**
     * Gets the WordPress {@see WP_Term} object for the supplied term ID.
     * We disable reads during this operation, as we want the data from the local database only.
     *
     * @param int $termId
     * @return WP_Term|null
     */
    protected function getWordPressTermObject(int $termId) : ?WP_Term
    {
        if (! $termId) {
            return null;
        }

        /*
         * We have to delete the object cache again to ensure that our next `getTerm()` call will load fresh data
         * from the database. This is because of this order of operations:
         *
         * 1. Click "Save" in the category UI.
         * 2. WordPress updates the database.
         * 3. WordPress clears the term cache (similar to below).
         * 4. Then WooCommerce hooks in and does something that triggers a new `get_term()` call. This results in
         *    a new API request to read from the service (which we haven't written to yet!), which then loads that
         *    outdated API info into the object cache.
         * 5. Then our below code runs. Without flushing the cache, our `getTerm()` call would be calling from the
         *    object cache, which had stored outdated information!
         *
         * The consequence of not clearing the cache first is:
         * 1. Updates will not get written to the API.
         * 2. Upon page refresh after clicking "Save", your changes will not have persisted.
         */
        wp_cache_delete($termId, 'terms');

        /** @var WP_Term|null $wpTerm */
        $wpTerm = CatalogIntegration::withoutReads(fn () => TermsRepository::getTerm($termId));

        return $wpTerm;
    }
}
