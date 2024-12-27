<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\AdapterException;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Extensions\AbstractExtension;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedExtensionsRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\WooCommerceSubscriptionAdapter;
use WC_Helper;

/**
 * Intercepts WooCommerce.com product subscriptions to include MWC Extensions.
 * We do this because otherwise extensions that GoDaddy includes for free show up as "Installed Extensions without a
 * Subscription", which is confusing to the merchant.
 */
class WooCommerceSubscriptionsInterceptor extends AbstractInterceptor
{
    /**
     * Registers hooks.
     *
     * @return void
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::filter()
            ->setGroup('transient__woocommerce_helper_subscriptions')
            ->setHandler([$this, 'maybeFilterSubscriptions'])
            ->execute();
    }

    /**
     * Determines whether the interceptor should load. We only want to load on the "WooCommerce.com Subscriptions" page.
     *
     * @return bool
     */
    public static function shouldLoad() : bool
    {
        return 'wc-addons' === ArrayHelper::get($_GET, 'page') &&
            'subscriptions' === ArrayHelper::get($_GET, 'tab') &&
            WooCommerceRepository::isWooCommerceActive();
    }

    /**
     * Filters the WooCommerce.com subscriptions array when on the "WooCommerce.com Subscriptions" page.
     *
     * @internal
     *
     * @param array<string, mixed>|mixed $transientValue
     * @return array<string, mixed>|mixed
     */
    public function maybeFilterSubscriptions($transientValue)
    {
        if (! is_array($transientValue)) {
            return $transientValue;
        }

        try {
            return $this->addMwcExtensionsToSubscriptions($transientValue);
        } catch(Exception $e) {
            return $transientValue;
        }
    }

    /**
     * Adds MWC Extensions to the list of WooCommerce.com Subscriptions.
     *
     * @param array<string, mixed> $subscriptions
     * @return array<string, mixed>
     * @throws BaseException|Exception|AdapterException
     */
    protected function addMwcExtensionsToSubscriptions(array $subscriptions) : array
    {
        $managedExtensionSlugs = $this->getManagedExtensionSlugs();

        foreach ($this->getLocalWooExtensions() as $localWooExtension) {
            // bail if this extension is not a Managed WooCommerce one
            if (empty($localWooExtension['slug']) || ! in_array($localWooExtension['slug'], $managedExtensionSlugs, true)) {
                continue;
            }

            $subscriptionData = WooCommerceSubscriptionAdapter::getNewInstance()->convertFromSource($localWooExtension);

            $subscriptions[$subscriptionData['product_key']] = $subscriptionData;
        }

        return $subscriptions;
    }

    /**
     * Gets the slugs of all managed extensions.
     *
     * @return string[]
     * @throws Exception
     */
    protected function getManagedExtensionSlugs() : array
    {
        return TypeHelper::arrayOfStrings(array_map(
            fn (AbstractExtension $extension) => $extension->getSlug(),
            ManagedExtensionsRepository::getManagedExtensions()
        ));
    }

    /**
     * Gets an array of all WooCommerce plugins and themes that are installed on the local site.
     *
     * @return array<string, array<string, mixed>>
     * @throws BaseException
     */
    protected function getLocalWooExtensions() : array
    {
        /** @var array<string, array<string, mixed>> $extensions */
        $extensions = ArrayHelper::combine(WC_Helper::get_local_woo_plugins(), WC_Helper::get_local_woo_themes());

        return $extensions;
    }
}
