<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers;

use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Repositories\Exceptions\WordPressRepositoryException;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\TermsRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;

class RemoteCategoryNotFoundHelper
{
    /**
     * Handles the case where there is a locally mapped category record but the commerce version returns a 404.
     *
     * @param int $localId
     * @return void
     */
    public function handle(int $localId) : void
    {
        try {
            /*
             * Disable reads, because `wp_delete_term()` issues a `get_term()`
             * call that we do not need to be routed to the platform.
             */
            CatalogIntegration::withoutReads(fn () => TermsRepository::deleteTerm($localId, CatalogIntegration::PRODUCT_CATEGORY_TAXONOMY));

            /* the mapping entry is automatically removed via the `delete_product_cat` hook in {@see LocalCategoryDeletedInterceptor} */
        } catch(WordPressRepositoryException $exception) {
            SentryException::getNewInstance('Failed to delete local copy of missing remote category.', $exception);
        }
    }
}
