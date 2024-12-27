<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\Handlers\AbstractInterceptorHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Enums\CommerceTables;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\CategoryMapRepository;

/**
 * Handler to respond to category deleted actions.
 */
class LocalCategoryDeletedHandler extends AbstractInterceptorHandler
{
    protected CategoryMapRepository $categoryMapRepository;

    /**
     * Constructor.
     *
     * @param CategoryMapRepository $categoryMapRepository
     */
    public function __construct(CategoryMapRepository $categoryMapRepository)
    {
        $this->categoryMapRepository = $categoryMapRepository;
    }

    /**
     * Executes the callback for {@see wp_delete_term()} actions.
     *
     * When a local category is deleted, we delete teh corresponding record from the {@see CommerceTables::ResourceMap} database table.
     *
     * @param ...$args
     * @return void
     */
    public function run(...$args)
    {
        $localCategoryId = TypeHelper::int(ArrayHelper::get($args, 0), 0);

        // note: the hook name is already tied to the `product_cat` taxonomy, which is why we don't need a taxonomy check here
        // @see LocalCategoryDeletedInterceptor::addHooks()
        if ($localCategoryId) {
            try {
                $this->categoryMapRepository->deleteByLocalId($localCategoryId);
            } catch(Exception $e) {
                SentryException::getNewInstance('Failed to handle deleted category.', $e);
            }
        }
    }
}
