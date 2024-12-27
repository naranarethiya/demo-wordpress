<?php

namespace GoDaddy\WordPress\MWC\Core\Client;

use BadMethodCallException;
use Exception;
use GoDaddy\WordPress\MWC\Common\Auth\AuthProviderFactory;
use GoDaddy\WordPress\MWC\Common\Auth\Contracts\AuthCredentialsContract;
use GoDaddy\WordPress\MWC\Common\Auth\Exceptions\AuthProviderException;
use GoDaddy\WordPress\MWC\Common\Auth\Exceptions\CredentialsCreateFailedException;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Enqueue\Enqueue;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\Contracts\HostingPlanContract;
use GoDaddy\WordPress\MWC\Common\Platforms\Contracts\PlatformRepositoryContract;
use GoDaddy\WordPress\MWC\Common\Providers\Contracts\ProviderContract;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Common\Stores\Contracts\StoreRepositoryContract;
use GoDaddy\WordPress\MWC\Core\Admin\Views\Components\PlatformContainerElement;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Configuration\Contracts\CartRecoveryEmailsFeatureRuntimeConfigurationContract;
use GoDaddy\WordPress\MWC\Core\HostingPlans\Repositories\HostingPlanRepository;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\Frontend\Admin\Notices;
use GoDaddy\WordPress\MWC\Dashboard\Menu\GetHelpMenu;
use GoDaddy\WordPress\MWC\Shipping\Shipping;

/**
 * MWC Client class.
 *
 * @since 2.10.0
 */
class Client
{
    /** @var string the app source, normally a URL */
    protected string $appSource;

    /** @var string the identifier of the application */
    protected string $appHandle;

    protected CartRecoveryEmailsFeatureRuntimeConfigurationContract $cartRecoveryEmailsFeatureRuntimeConfiguration;

    protected PlatformRepositoryContract $platformRepository;

    protected HostingPlanContract $hostingPlan;

    protected StoreRepositoryContract $storeRepository;

    /**
     * MWC Client constructor.
     *
     * @throws Exception
     */
    public function __construct(
        CartRecoveryEmailsFeatureRuntimeConfigurationContract $cartRecoveryEmailsFeatureRuntimeConfiguration,
        PlatformRepositoryContract $platformRepository,
        HostingPlanContract $hostingPlan,
        StoreRepositoryContract $storeRepository
    ) {
        $this->hostingPlan = $hostingPlan;
        $this->platformRepository = $platformRepository;
        $this->cartRecoveryEmailsFeatureRuntimeConfiguration = $cartRecoveryEmailsFeatureRuntimeConfiguration;
        $this->storeRepository = $storeRepository;
        $this->appHandle = 'mwcClient';
        $this->appSource = TypeHelper::string(Configuration::get('mwc.client.index.url'), '');

        $this->registerHooks();
    }

    /**
     * Registers the client's hook handlers.
     *
     * @since 2.10.0
     *
     * @return Client
     * @throws Exception
     */
    protected function registerHooks() : Client
    {
        Register::action()
            ->setGroup('admin_body_class')
            ->setHandler([$this, 'addAdminBodyClasses'])
            ->execute();

        Register::action()
            ->setGroup('admin_enqueue_scripts')
            ->setHandler([$this, 'enqueueAssets'])
            ->execute();

        Register::action()
            ->setGroup('admin_print_styles')
            ->setHandler([$this, 'enqueueMessagesContainerStyles'])
            ->execute();

        Register::action()
            ->setGroup('all_admin_notices')
            ->setHandler([$this, 'renderMessagesContainer'])
            ->execute();

        Register::action()
            ->setGroup('admin_footer')
            ->setHandler([$this, 'renderPlatformAppContainer'])
            ->execute();

        return $this;
    }

    /**
     * Adds utility classes to the body element in the admin dashboard.
     *
     * @internal
     *
     * @since 2.1.4
     *
     * @param string $classes space separated list of classes for the body element in the admin dashboard
     */
    public function addAdminBodyClasses($classes)
    {
        if (! $version = $this->getWooCommerceVersion()) {
            return;
        }

        if (version_compare($version, '5.2.0', '>=')) {
            $classes .= ' mwc-wc-version-gte-5-2';
        }

        return $classes;
    }

    /**
     * Gets the currently active version of WooCommerce or null if the plugin is not active.
     *
     * We can't use woocommerce.version configuration value because that's currently always set to null.
     *
     * @since 2.1.4
     *
     * @return string|null
     */
    protected function getWooCommerceVersion()
    {
        return defined('WC_VERSION') ? constant('WC_VERSION') : null;
    }

    /**
     * Render the styles for the container div.
     *
     * @since 2.10.0
     *
     * @return void
     * @throws Exception
     */
    public function enqueueMessagesContainerStyles()
    {
        Enqueue::style()
            ->setHandle("{$this->appHandle}-main-styles")
            ->setSource(StringHelper::trailingSlash(Configuration::get('mwc.assets.styles')).'main.css')
            ->execute();

        Enqueue::style()
            ->setHandle("{$this->appHandle}-messages-styles")
            ->setSource(WordPressRepository::getAssetsUrl('css/mwc-messages-container.css'))
            ->execute();
    }

    /**
     * Render the styles for the container div.
     *
     * @since 2.10.0
     *
     * @return void
     * @throws Exception
     */
    public function renderMessagesContainer()
    {
        ?>
        <div id="mwc-messages-container" class="mwc-messages-container"></div>
        <?php
    }

    /**
     * Renders the container div for the platform app.
     *
     * @since 2.10.0
     *
     * @return void
     * @throws Exception
     */
    public function renderPlatformAppContainer()
    {
        PlatformContainerElement::renderIfNotRendered();
    }

    /**
     * Enqueues/loads registered assets.
     *
     * @since 2.10.0
     *
     * @throws Exception
     */
    public function enqueueAssets()
    {
        Enqueue::script()
            ->setHandle("{$this->appHandle}-runtime")
            ->setSource(Configuration::get('mwc.client.runtime.url'))
            ->setDeferred(true)
            ->execute();

        Enqueue::script()
            ->setHandle("{$this->appHandle}-vendors")
            ->setSource(Configuration::get('mwc.client.vendors.url'))
            ->setDeferred(true)
            ->execute();

        $this->enqueueApp();
        $this->enqueueNoticesAssets();
        $this->maybeEnqueueFullStoryAssets();
    }

    /**
     * Enqueues the single page application script.
     *
     * @since 2.10.0
     *
     * @throws Exception
     */
    protected function enqueueApp()
    {
        $script = Enqueue::script()
            ->setHandle($this->appHandle)
            ->setSource($this->appSource)
            ->setDeferred(true);

        $inlineScriptVariables = $this->getInlineScriptVariables();

        if (! empty($inlineScriptVariables)) {
            $script->attachInlineScriptObject($this->appHandle)
                ->attachInlineScriptVariables($inlineScriptVariables);
        }

        $script->execute();
    }

    /**
     * Gets inline script variables.
     *
     * @return array<string, mixed>
     * @throws Exception
     */
    protected function getInlineScriptVariables() : array
    {
        return array_merge(
            $this->getFeatureFlagsContextVariables(),
            $this->getClientContextVariables(),
            $this->getPageContextVariables(),
            $this->getPermissionsContextVariables(),
            $this->getPlanContextVariables(),
            $this->getPlanPermissionsContextVariables(),
            $this->getShippingContextVariables(),
            $this->getEventsVariables(),
            $this->getCartRecoveryEmailsContextVariables(),
            $this->getStoreVariables()
        );
    }

    /**
     * Get frontend events variables.
     *
     * @return array
     */
    protected function getEventsVariables() : array
    {
        if (! current_user_can('manage_woocommerce')) {
            return [];
        }

        try {
            $token = $this->getEventsCredentialsFromAuthProvider()->toArray();
        } catch (Exception $exception) {
            $token = null;
        }

        return [
            'events' => [
                'token' => $token,
            ],
        ];
    }

    /**
     * Gets the credentials for the Events API using an auth provider.
     *
     * @return AuthCredentialsContract
     * @throws AuthProviderException
     * @throws CredentialsCreateFailedException
     */
    protected function getEventsCredentialsFromAuthProvider() : AuthCredentialsContract
    {
        return AuthProviderFactory::getNewInstance()->getEventsAuthProvider()->getCredentials();
    }

    /**
     * Gets inline script variables for feature flags.
     *
     * @return array
     */
    protected function getFeatureFlagsContextVariables() : array
    {
        return ['featureFlags' => Features::getNewInstance()->featureFlags()];
    }

    /**
     * Gets the default inline script variables for the client.
     *
     * @since 2.10.0
     *
     * @return array
     */
    protected function getClientContextVariables() : array
    {
        return [
            'root'  => esc_url_raw(rest_url()),
            'nonce' => wp_create_nonce('wp_rest'),
        ];
    }

    /**
     * Gets inline script variables that describe the current page.
     *
     * @since 2.10.0
     *
     * @return array
     */
    protected function getPageContextVariables() : array
    {
        $currentScreen = WordPressRepository::getCurrentScreen();

        return $currentScreen ? $currentScreen->toArray() : [];
    }

    /**
     * Gets the inline script variables related to the current user's permissions.
     *
     * @since 2.10.0
     *
     * @return array
     * @throws Exception
     */
    protected function getPermissionsContextVariables() : array
    {
        return [
            'api'            => current_user_can('edit_posts'),
            'installPlugins' => current_user_can('install_plugins') && current_user_can('activate_plugins'),
            'getHelp'        => current_user_can(GetHelpMenu::CAPABILITY) && GetHelpMenu::shouldLoadConditionalFeature(),
        ];
    }

    /**
     * Gets the last upgrade date from the hosting plan, and formats it in ISO 8601 format if it exists.
     * @note DATE_ATOM is ISO-8601 compatible.
     *
     * @return string|null
     */
    protected function getContextualUpgradeDate() : ?string
    {
        $date = HostingPlanRepository::getNewInstance()->getUpgradeDateTime();

        return $date ? $date->format(DATE_ATOM) : null;
    }

    /**
     * Gets the inline script variables related to the site plan.
     *
     * @return array<string, mixed>
     */
    protected function getPlanContextVariables() : array
    {
        $plan = $this->hostingPlan->toArray();
        $plan['lastUpgradeTimestamp'] = $this->getContextualUpgradeDate();

        return ['plan' => $plan];
    }

    /**
     * Gets the inline script variables related to the site's plan permissions.
     *
     * @return array<string, array<string, bool>>
     */
    protected function getPlanPermissionsContextVariables() : array
    {
        return [
            'planPermissions' => [
                // @TODO Replace this upload check with a more general permission call when that is added in MWC-8327 {agibson 2022-09-26}
                'canUploadExtensions' => ! $this->hostingPlan->isTrial(),
            ],
        ];
    }

    /**
     * Gets inline script variables that describe the available shipping providers.
     *
     * @since 2.10.0
     *
     * @return array
     */
    protected function getShippingContextVariables() : array
    {
        return [
            'shipping' => [
                'providers' => $this->getShippingProvidersData(),
            ],
        ];
    }

    /**
     * Gets data for the registered shipping providers.
     *
     * @since 2.10.0
     *
     * @return array
     */
    protected function getShippingProvidersData() : array
    {
        $providers = array_values(array_map(function (ProviderContract $provider) {
            return [
                'label'       => $provider->getLabel(),
                'name'        => $provider->getName(),
                'trackingUrl' => $this->getTrackingUrlTemplate($provider),
            ];
            /* @phpstan-ignore-next-line */
        }, Shipping::getInstance()->getProviders()));

        $providers[] = [
            'label'       => __('Other', 'mwc-core'),
            'name'        => 'other',
            'trackingUrl' => null,
        ];

        return $providers;
    }

    /**
     * Gets inline variables that describe the Cart Recovery Emails feature.
     *
     * @return array<string, mixed>
     */
    protected function getCartRecoveryEmailsContextVariables() : array
    {
        return [
            'cartRecoveryEmails' => [
                'allowedSeriesLength' => $this->getAllowedSeriesLength(),
                'isDelayReadOnly'     => $this->isDelayReadOnly(),
            ],
        ];
    }

    /**
     * Gets the number of cart recovery emails allowed for the site.
     *
     * @return int
     */
    protected function getAllowedSeriesLength() : int
    {
        return $this->cartRecoveryEmailsFeatureRuntimeConfiguration->getNumberOfCartRecoveryEmails();
    }

    /**
     * Determines whether the delay for cart recovery email notifications should be read only.
     *
     * @return bool
     */
    protected function isDelayReadOnly() : bool
    {
        return $this->cartRecoveryEmailsFeatureRuntimeConfiguration->isDelayReadOnly();
    }

    /**
     * Gets inline variables related to the Commerce store.
     *
     * @return array<string, mixed>
     */
    protected function getStoreVariables() : array
    {
        $storeId = $this->storeRepository->getStoreId();

        return [
            'store' => [
                'defaultStoreId'    => $storeId,
                'hasDefaultStoreId' => ! empty($storeId),
            ],
        ];
    }

    /**
     * Returns the tracking URL template for the given provider, if any.
     *
     * @param ProviderContract $provider
     *
     * @return string|null
     */
    protected function getTrackingUrlTemplate(ProviderContract $provider) : ?string
    {
        try {
            $tracking = $provider->tracking();
        } catch (BadMethodCallException $e) {
            return null;
        }

        if (! is_callable([$tracking, 'getTrackingUrlTemplate'])) {
            return null;
        }

        return $tracking->getTrackingUrlTemplate();
    }

    /**
     * Enqueues the notices script.
     *
     * @throws Exception
     */
    protected function enqueueNoticesAssets() : void
    {
        Enqueue::script()
            ->setHandle("{$this->appHandle}-notices")
            ->setSource(WordPressRepository::getAssetsUrl('js/notices.js'))
            ->setDeferred(true)
            ->attachInlineScriptObject('MWCNotices')
            ->attachInlineScriptVariables([
                'dismissNoticeAction' => Notices::ACTION_DISMISS_NOTICE,
            ])
            ->execute();
    }

    /**
     * Enqueues the FullStory script, if it's enabled.
     *
     * @return void
     * @throws Exception
     */
    protected function maybeEnqueueFullStoryAssets() : void
    {
        Enqueue::script()
            ->setHandle("{$this->appHandle}-fullstory")
            ->setSource(WordPressRepository::getAssetsUrl('js/fullstory.js'))
            ->setDeferred(true)
            ->setCondition(function () {
                return true === Configuration::get('mwc.fullStory.enabled');
            })
            ->attachInlineScriptObject('MWCFullStory')
            ->attachInlineScriptVariables([
                'customerId' => $this->platformRepository->getGoDaddyCustomerId(),
                'channelId'  => $this->platformRepository->getChannelId(),
                'siteId'     => $this->platformRepository->getPlatformSiteId(),
                'storeId'    => $this->storeRepository->getStoreId(),
            ])
            ->execute();
    }
}
