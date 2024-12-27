<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Factories;

use Exception;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\WpQueryInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\AbstractCachingService;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\CachingStrategyFactoryContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Strategies\Contracts\CachingStrategyContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Strategies\Contracts\PersistentCachingStrategyContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Strategies\MemoryCachingStrategy;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Strategies\MemoryWithPersistenceCachingStrategy;

/**
 * Caching strategy factory that contextually switches between in-memory and persistent.
 */
class CachingStrategyFactory implements CachingStrategyFactoryContract
{
    use CanGetNewInstanceTrait;

    /** @var MemoryCachingStrategy the memory caching strategy injected instance */
    protected MemoryCachingStrategy $memoryCachingStrategy;

    /** @var PersistentCachingStrategyContract the persistent caching strategy injected instance */
    protected PersistentCachingStrategyContract $persistentCachingStrategy;

    /** @var MemoryWithPersistenceCachingStrategy the caching strategy based on memory with persistence */
    protected MemoryWithPersistenceCachingStrategy $memoryWithPersistenceCachingStrategy;

    /**
     * Constructor.
     *
     * @param MemoryCachingStrategy $memoryCachingStrategy
     * @param PersistentCachingStrategyContract $persistentCachingStrategy
     */
    public function __construct(MemoryCachingStrategy $memoryCachingStrategy, PersistentCachingStrategyContract $persistentCachingStrategy)
    {
        $this->memoryCachingStrategy = $memoryCachingStrategy;
        $this->persistentCachingStrategy = $persistentCachingStrategy;
    }

    /**
     * Returns caching strategy based whether the cart, checkout, or product admin pages are the current screen.
     *
     * @return CachingStrategyContract
     */
    public function makeCachingStrategy() : CachingStrategyContract
    {
        if ($this->canUsePersistentCachingStrategy()) {
            return $this->persistentCachingStrategy;
        }

        return $this->memoryWithPersistenceCachingStrategy ??= new MemoryWithPersistenceCachingStrategy(
            $this->memoryCachingStrategy,
            $this->persistentCachingStrategy
        );
    }

    /**
     * Determines if the persistent caching strategy can be used.
     *
     * @return bool
     */
    protected function canUsePersistentCachingStrategy() : bool
    {
        return ! ($this->isOnePageCheckout() || $this->isCartOrCheckout() || $this->isAdminProductPage());
    }

    /**
     * Determines if WooCommerce One Page Checkout is enabled and has started running its conditional checks.
     *
     * @link https://godaddy-corp.atlassian.net/browse/MWC-14790
     *
     * One Page Checkout can cause an infinite loop when combined with our `isCheckoutPage()` call in {@see static::isCartOrCheckout()}.
     * OPC hooks into the `woocommerce_is_checkout` filter via {@see \PP_One_Page_Checkout::is_checkout_filter()}.
     * This calls {@see \PP_One_Page_Checkout::is_wcopc_checkout()}.
     * This calls a WordPress core function {@see url_to_postid()} (referenced in the method implementation below).
     * This executes a new WP_Query, which triggers the `posts_results` filter.
     *     The WP Query is searching for a `product` by slug, which is what causes the step below to trigger.
     * Products integration hooks into `posts_results` {@see WpQueryInterceptor::addHooks()} to query for Commerce data.
     * This triggers {@see AbstractCachingService::getCachingStrategy()} as part of determining if the query is already cached.
     * This leads us to this class, to check if we're on the checkout page.
     * This calls {@see is_checkout()}, which leads us back to the first step... the One Page Checkout filter on that function.
     *
     * The method below is a rudimentary attempt at determining if One Page Checkout is executing its conditional code.
     * It's not perfect, but we don't have a better idea at this time!
     * It basically means any time One Page Checkout is activated and has run its conditionals, we will be using in memory caching.
     *
     * @return bool
     */
    protected function isOnePageCheckout() : bool
    {
        return class_exists('PP_One_Page_Checkout') && did_filter('url_to_postid') > 0;
    }

    /**
     * Determines if the cart or checkout pages are the current screen.
     *
     * @return bool
     */
    protected function isCartOrCheckout() : bool
    {
        return WooCommerceRepository::isCartPage() ||
            WooCommerceRepository::isCheckoutPage() ||
            WooCommerceRepository::isCheckoutPayPage();
    }

    /**
     * Determines if the current screen is the edit product page in admin area.
     *
     * @NOTE: This method will always return false if it's called prior to the WordPress admin_init hook.
     *
     * @return bool
     */
    protected function isAdminProductPage() : bool
    {
        $screensToCheck = ['edit-product', 'product'];

        try {
            // inside a try/catch block to safely execute in case the WordPress admin_init hook wasn't called yet
            return WordPressRepository::isAdmin() && WordPressRepository::isCurrentScreen($screensToCheck);
        } catch (Exception $e) {
            return false;
        }
    }
}
