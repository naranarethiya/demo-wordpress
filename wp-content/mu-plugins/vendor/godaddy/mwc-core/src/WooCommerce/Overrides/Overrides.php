<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Overrides;

use Automattic\WooCommerce\Admin\Features\PaymentGatewaySuggestions\DefaultPaymentGateways;
use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ConditionalComponentContract;
use GoDaddy\WordPress\MWC\Common\Components\Traits\HasComponentsTrait;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Http\GoDaddyRequest;
use GoDaddy\WordPress\MWC\Common\Platforms\Exceptions\PlatformRepositoryException;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use WC_Admin_Notices;

class Overrides implements ConditionalComponentContract
{
    use HasComponentsTrait;
    /** @var array alphabetically ordered list of components to load */
    protected $componentClasses = [
        RedirectToEmailSettings::class,
    ];

    /**
     * Initializes the component.
     *
     * @throws Exception
     */
    public function load()
    {
        $this->loadComponents();
        $this->registerActions();
        $this->registerFilters();
    }

    /**
     * Registers actions.
     *
     * @throws Exception
     */
    private function registerActions()
    {
        Register::action()
            ->setGroup('plugins_loaded')
            ->setHandler([$this, 'setDefaults'])
            ->setPriority(PHP_INT_MAX)
            ->setArgumentsCount(0)
            ->execute();

        // may disable marketplace suggestions
        Register::action()
            ->setGroup('admin_init')
            ->setHandler([$this, 'maybeDisableMarketplaceSuggestions'])
            ->setArgumentsCount(0)
            ->execute();
    }

    /**
     * Registers filters.
     *
     * @throws Exception
     */
    private function registerFilters()
    {
        Register::filter()
            ->setGroup('woocommerce_show_admin_notice')
            ->setHandler([$this, 'suppressNotices'])
            ->setPriority(10)
            ->setArgumentsCount(2)
            ->execute();

        Register::filter()
            ->setGroup('woocommerce_helper_suppress_connect_notice')
            ->setHandler([$this, 'suppressConnectNotice'])
            ->setPriority(PHP_INT_MAX)
            ->setArgumentsCount(1)
            ->execute();

        Register::filter()
            ->setGroup('woocommerce_hidden_order_itemmeta')
            ->setHandler([$this, 'hideOrderItemMeta'])
            ->execute();

        Register::filter()
            ->setGroup('wc_pdf_product_vouchers_admin_hide_low_memory_notice')
            ->setHandler([$this, 'hidePdfProductVouchersLowMemoryNotice'])
            ->setPriority(10)
            ->setArgumentsCount(1)
            ->execute();

        Register::filter()
            ->setGroup('wc_pdf_product_vouchers_admin_hide_sucuri_notice')
            ->setHandler([$this, 'hidePdfProductVouchersSucuriNotice'])
            ->setPriority(10)
            ->setArgumentsCount(1)
            ->execute();

        // add the authentication headers necessary for getting packages from the Extensions API
        Register::filter()
            ->setGroup('http_request_args')
            ->setHandler([$this, 'addExtensionsApiAuthenticationHeaders'])
            ->setPriority(10)
            ->setArgumentsCount(2)
            ->execute();

        // ensure checkout is always HTTPS for temp sites
        Register::filter()
            ->setGroup('pre_option_woocommerce_force_ssl_checkout')
            ->setCondition(function () {
                return PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->hasEcommercePlan();
            })
            ->setHandler([$this, 'maybeSetForceSsl'])
            ->setPriority(10)
            ->setArgumentsCount(1)
            ->execute();

        Register::filter()
            ->setGroup('pre_option_woocommerce_task_list_hidden')
            ->setHandler([$this, 'hideWooCommerceTaskList'])
            ->setCondition(function () {
                return PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->hasEcommercePlan();
            })
            ->setPriority(PHP_INT_MAX)
            ->execute();

        Register::filter()
            ->setGroup('woocommerce_admin_payment_gateway_suggestion_specs')
            ->setArgumentsCount(1)
            ->setPriority(PHP_INT_MIN)
            ->setHandler([$this, 'removeWoocommercePaymentsGateways'])
            ->execute();
    }

    /**
     * Returns a flag to hide WooCommerce setup widget in the dashboard.
     *
     * @internal
     *
     * @return string
     */
    public function hideWooCommerceTaskList() : string
    {
        return 'yes';
    }

    /**
     * Set option defaults for a better experience on the MWP eCommerce plan.
     *
     * @action plugins_loaded - PHP_INT_MAX
     */
    public function setDefaults()
    {
        try {
            if (! PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->hasEcommercePlan()) {
                return;
            }
        } catch (PlatformRepositoryException $exception) {
            return;
        }

        if (class_exists('WC_Admin_Notices') && ! ManagedWooCommerceRepository::hasCompletedWPNuxOnboarding()) {
            WC_Admin_Notices::remove_notice('install', true);
        }

        if ('no' !== get_option('woocommerce_onboarding_opt_in')) {
            update_option('woocommerce_onboarding_opt_in', 'no');
        }

        if ('yes' !== get_option('woocommerce_task_list_hidden')) {
            update_option('woocommerce_task_list_hidden', 'yes');
        }

        $onboarding_profile = (array) get_option('woocommerce_onboarding_profile', []);

        if (empty($onboarding_profile['completed'])) {
            update_option('woocommerce_onboarding_profile', array_merge($onboarding_profile, ['completed' => true]));
        }
    }

    /**
     * Adds the authentication headers necessary for getting packages from the Extensions API.
     *
     * @internal
     *
     * @param mixed $requestArgs request args
     * @param string $url request URL
     *
     * @return mixed
     */
    public function addExtensionsApiAuthenticationHeaders($requestArgs, string $url)
    {
        // target admin extensions API requests only
        if ((WordPressRepository::isAdmin() || WordPressRepository::isApiRequest())
            && $this->isExtensionsApiEndpoint($url)) {
            $requestArgs = $this->mergeRequestHeaders($requestArgs, $this->getGoDaddyRequestInstance());
        }

        return $requestArgs;
    }

    /**
     * Determines if the supplied URL is an /extensions endpoint.
     *
     * @param string $url
     * @return bool
     */
    protected function isExtensionsApiEndpoint(string $url) : bool
    {
        $extensionsApiUrl = StringHelper::trailingSlash(ManagedWooCommerceRepository::getApiUrl()).'extensions';

        return StringHelper::contains($url, $extensionsApiUrl);
    }

    /**
     * Gets an instance of GoDaddy API Request class.
     *
     * @return GoDaddyRequest
     */
    protected function getGoDaddyRequestInstance() : GoDaddyRequest
    {
        return new GoDaddyRequest();
    }

    /**
     * Updates the given array of requests args by adding the headers from the given {@see GoDaddyRequest}.
     *
     * @param array $requestArgs
     * @param GoDaddyRequest $request
     * @return array
     */
    protected function mergeRequestHeaders(array $requestArgs, GoDaddyRequest $request) : array
    {
        try {
            $request->addHeaders(ArrayHelper::wrap(ArrayHelper::get($requestArgs, 'headers', [])));
        } catch (Exception $exception) {
            // ignore exception that can only occur if the argument for addHeaders() or the headers are not arrays
        }

        ArrayHelper::set($requestArgs, 'headers', $request->headers);

        return $requestArgs;
    }

    /**
     * Ensures checkout is always HTTPS for temp sites.
     *
     * @internal
     *
     * @param string|mixed $value
     * @return string|mixed
     * @throws PlatformRepositoryException
     */
    public function maybeSetForceSsl($value)
    {
        if (PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->isTemporaryDomain()) {
            return 'yes';
        }

        return $value;
    }

    /**
     * Callback for the woocommerce_helper_suppress_connect_notice filter.
     *
     * @internal
     *
     * @return bool
     * @throws PlatformRepositoryException
     */
    public function suppressConnectNotice()
    {
        return PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->hasEcommercePlan();
    }

    /**
     * Adds meta keys to the list of order item meta keys that should be hidden.
     *
     * @internal
     *
     * @param array $keys hidden order item meta keys
     * @return array
     * @throws BaseException
     */
    public function hideOrderItemMeta($keys)
    {
        return ArrayHelper::combine(ArrayHelper::wrap($keys), Configuration::get('woocommerce.hiddenOrderItemMeta', []));
    }

    /**
     * Callback for the wc_pdf_product_vouchers_admin_hide_low_memory_notice filter.
     *
     * @internal
     *
     * @return bool
     */
    public function hidePdfProductVouchersLowMemoryNotice()
    {
        return true;
    }

    /**
     * Callback for the wc_pdf_product_vouchers_admin_hide_sucuri_notice filter.
     *
     * @internal
     *
     * @return bool
     */
    public function hidePdfProductVouchersSucuriNotice()
    {
        return true;
    }

    /**
     * Suppress WooCommerce admin notices.
     *
     * @filter woocommerce_show_admin_notice - 10
     *
     * @param bool $bool Boolean value to show/suppress the notice.
     * @param string $notice The notice name being displayed.
     * @return bool True to show the notice, false to suppress it.
     * @throws PlatformRepositoryException
     */
    public function suppressNotices($bool, $notice)
    {
        // Suppress the SSL notice when hosted on MWP on a temp domain.
        if ('no_secure_connection' === $notice && PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->isTemporaryDomain()) {
            return false;
        }

        // Suppress the "Install WooCommerce Admin" notice when the Setup Wizard notice is visible.
        if ('wc_admin' === $notice && in_array('install', (array) get_option('woocommerce_admin_notices', []), true)) {
            return false;
        }

        return $bool;
    }

    /**
     * Callback to determine whether to disable the marketplace suggestions.
     *
     * @internal
     *
     * @throws Exception
     */
    public function maybeDisableMarketplaceSuggestions()
    {
        if ($this->shouldDisableMarketplaceSuggestions()) {
            $this->disableMarketplaceSuggestions();
        }
    }

    /**
     * Disables marketplace suggestions.
     *
     * @throws Exception
     */
    protected function disableMarketplaceSuggestions()
    {
        update_option('woocommerce_show_marketplace_suggestions', 'no');

        update_option('gd_mwc_disable_woocommerce_marketplace_suggestions', 'no');

        Configuration::set('woocommerce.flags.disableMarketplaceSuggestions', 'no');
    }

    /**
     * Removes payment gateways that use the `woocommerce-payments` plugins from a list of available gateways.
     *
     * @see DefaultPaymentGateways::get_all()
     *
     * @param array<mixed> $gatewaySuggestions
     * @return array<mixed>
     */
    public function removeWoocommercePaymentsGateways(array $gatewaySuggestions) : array
    {
        $keysToRemove = array_keys(ArrayHelper::where($gatewaySuggestions, function ($gateway) {
            return ArrayHelper::has($gateway, 'plugins') && ArrayHelper::contains($gateway['plugins'], 'woocommerce-payments');
        }));

        return ArrayHelper::except($gatewaySuggestions, $keysToRemove);
    }

    /**
     * Determines whether the marketplace suggestions should be disabled.
     *
     * @return bool
     *
     * @throws Exception
     */
    private function shouldDisableMarketplaceSuggestions() : bool
    {
        // 1617235200 is the timestamp for April 1, 2021
        $isSiteCreatedAfterApril2021 = Configuration::get('godaddy.site.created', 0) >= 1617235200;

        return $isSiteCreatedAfterApril2021 && 'yes' === Configuration::get('woocommerce.flags.disableMarketplaceSuggestions');
    }

    /**
     * Determines whether to load the feature.
     *
     * @return bool
     * @throws Exception
     */
    public static function shouldLoad() : bool
    {
        return WooCommerceRepository::isWooCommerceActive();
    }
}
