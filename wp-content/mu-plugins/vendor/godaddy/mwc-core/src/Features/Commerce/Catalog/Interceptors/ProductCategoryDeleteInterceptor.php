<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Register\Types\RegisterFilter;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\TermsRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetEnvironmentBasedConfigValueTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\CatalogIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Commerce;
use WP_Term;
use WP_User;

class ProductCategoryDeleteInterceptor extends AbstractInterceptor
{
    use CanGetEnvironmentBasedConfigValueTrait;

    /**
     * {@inheritDoc}
     *
     * @return void
     * @throws Exception
     */
    public function addHooks()
    {
        $this->getUserHasCapFilter()->execute();

        Register::filter()
            ->setGroup('product_cat_row_actions')
            ->setArgumentsCount(2)
            ->setHandler([$this, 'maybeAddDeleteNoticeRowAction'])
            ->execute();
    }

    /**
     * Gets the user_has_cap filter instance.
     *
     * @return RegisterFilter
     */
    protected function getUserHasCapFilter() : RegisterFilter
    {
        return Register::filter()
            ->setGroup('user_has_cap')
            ->setArgumentsCount(4)
            ->setPriority(PHP_INT_MAX)
            ->setHandler([$this, 'maybeRemoveDeleteProductTermsCapability']);
    }

    /**
     * Removes the delete_term capability for product categories if the current user has it.
     *
     * @param array<string, bool> $allCaps
     * @param string[]|mixed $caps
     * @param mixed[]|mixed $args
     * @param WP_User|mixed $user
     * @return mixed The filtered capabilities.
     */
    public function maybeRemoveDeleteProductTermsCapability($allCaps, $caps, $args, $user)
    {
        $allCaps = ArrayHelper::wrap($allCaps);

        $requestedCapability = TypeHelper::string(ArrayHelper::get($args, 0), '');
        $termIdToDelete = TypeHelper::int(ArrayHelper::get($args, 2), 0);

        // if we are not requesting the delete_term capability or don't have a term id to delete, return $allCaps.
        if ($requestedCapability !== 'delete_term' || $termIdToDelete == 0) {
            return $allCaps;
        }

        // only prevent deletes on `product_cat` taxonomy
        $term = CatalogIntegration::withoutReads(fn () => TermsRepository::getTerm($termIdToDelete, CatalogIntegration::PRODUCT_CATEGORY_TAXONOMY));
        if (! $term instanceof WP_Term) {
            return $allCaps;
        }

        // Disable the `delete_product_terms` capability
        $allCaps['delete_product_terms'] = false;

        return $allCaps;
    }

    /**
     * Replaces the delete row action with an external link to Commerce Home category screen.
     *
     * @internal
     *
     * @param array<string, string>|mixed $actions
     * @param WP_Term|mixed $term
     * @return array<string, string>|mixed
     */
    public function maybeAddDeleteNoticeRowAction($actions, $term)
    {
        if (! ArrayHelper::accessible($actions) || ! $term instanceof WP_Term) {
            return $actions;
        }

        if ($this->shouldDisplayCannotDeleteInWpAdminNotice(TypeHelper::int($term->term_id, 0))) {
            try {
                $actions = ArrayHelper::insertBeforeKey($actions, ['gd_delete' => $this->getCannotDeleteInWpAdminNotice()], 'view');
            } catch(BaseException $e) {
                SentryException::getNewInstance('Failed to add "delete in Commerce Home" notice to user row actions.', $e);
            }
        }

        return $actions;
    }

    /**
     * Determines whether we should display the deletion notice {@see static::getCannotDeleteInWpAdminNotice()}.
     *
     * @param int $termIdToDelete
     * @return bool
     */
    protected function shouldDisplayCannotDeleteInWpAdminNotice(int $termIdToDelete) : bool
    {
        $shouldDisplay = false;

        try {
            // Temporarily disable the user_has_cap filter.
            $this->getUserHasCapFilter()->deregister();

            $shouldDisplay = current_user_can('delete_term', $termIdToDelete);

            // Re-enable the user_has_cap filter.
            $this->getUserHasCapFilter()->execute();
        } catch(Exception $exception) {
        }

        return $shouldDisplay;
    }

    /**
     * Gets the notice that the main administrator account cannot be deleted.
     *
     * @return string
     */
    protected function getCannotDeleteInWpAdminNotice() : string
    {
        $categoriesUrl = $this->getCommerceHomeCategoriesUrl();

        return sprintf('<span class="delete"><a href="%1$s" class="aria-button-if-js" target="_blank" title="%2$s" aria-label="%3$s">%4$s</a></span>',
            esc_url($categoriesUrl),
            esc_attr__('Product categories must be deleted in Commerce Home', 'mwc-core'),
            esc_attr__('Delete product category', 'mwc-core'),
            /* translators: External link icon */
            sprintf(__('Delete %s', 'mwc-core'), '<span class="dashicons dashicons-external"></span>')
        );
    }

    /**
     * Gets the Categories URL for Commerce Home for the current store.
     *
     * @return string
     */
    protected function getCommerceHomeCategoriesUrl() : string
    {
        $storeId = TypeHelper::string(Commerce::getStoreId(), '');
        $categoriesUrl = TypeHelper::string($this->getEnvironmentConfigValue('commerce.catalog.website.categoriesUrl'), '');

        return add_query_arg('storeId', $storeId, $categoriesUrl);
    }
}
