<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GiftCertificates;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Traits\HasComponentsTrait;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Events\FeatureEnabledEvent;
use GoDaddy\WordPress\MWC\Common\Features\AbstractFeature;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Platforms\Exceptions\PlatformRepositoryException;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Features\GiftCertificates\Integrations\PDFProductVouchersIntegration;
use GoDaddy\WordPress\MWC\GiftCertificates\MWC_Gift_Certificates_Loader;

/**
 * The Gift Certificates feature loader.
 */
class GiftCertificates extends AbstractFeature
{
    use HasComponentsTrait;
    /** @var string name of the option used to determine whether we should broadcast the feature enabled event */
    private $featureEnabledEventOptionName = 'mwc_broadcast_gift_certificates_feature_enabled_event';

    /** @var string[] component classes to load */
    protected $componentClasses = [
        PDFProductVouchersIntegration::class,
    ];

    /**
     * Gets the feature name, matching the key used in configuration.
     *
     * @return string
     */
    public static function getName() : string
    {
        return 'gift_certificates';
    }

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function load()
    {
        $this->loadCommunityPlugin();
        $this->loadComponents();
        $this->registerHooks();
    }

    /**
     * Loads the community plugin.
     */
    protected function loadCommunityPlugin()
    {
        $rootVendorPath = StringHelper::trailingSlash(Configuration::get('system_plugin.project_root').'/vendor');

        // Load plugin class file
        require_once $rootVendorPath.'godaddy/mwc-gift-certificates/woocommerce-pdf-product-vouchers.php';

        // load SV Framework from root vendor folder first
        require_once $rootVendorPath.'skyverge/wc-plugin-framework/woocommerce/class-sv-wc-plugin.php';

        MWC_Gift_Certificates_Loader::instance()->init_plugin();
    }

    /**
     * Register the hooks for the Gift Certificates feature.
     *
     * @throws Exception
     */
    protected function registerHooks()
    {
        Register::action()
            ->setGroup('admin_init')
            ->setCondition([$this, 'shouldBroadcastFeatureEnabledEvent'])
            ->setHandler([$this, 'broadcastFeatureEnabledEvent'])
            ->execute();

        Register::action()
            ->setGroup('admin_head')
            ->setHandler([$this, 'registerGoDaddyBrandingHooks'])
            ->execute();

        Register::filter()
                ->setGroup('mwc_gift_certificates_should_add_wp_cron_disabled_notice')
                ->setHandler([$this, 'shouldAddWpCronDisabledNotice'])
                ->execute();
    }

    /**
     * Registers hooks needed for adding GoDaddy branding.
     *
     * @internal
     *
     * @throws Exception
     */
    public function registerGoDaddyBrandingHooks()
    {
        Register::filter()
            ->setGroup('admin_footer_text')
            ->setHandler('__return_empty_string')
            ->setCondition([$this, 'shouldAddGoDaddyBranding'])
            ->execute();

        Register::filter()
            ->setGroup('update_footer')
            ->setHandler('__return_empty_string')
            ->setCondition([$this, 'shouldAddGoDaddyBranding'])
            ->setPriority(PHP_INT_MAX)
            ->execute();
    }

    /**
     * Checks if it should add GoDaddy branding to feature pages.
     *
     * @internal
     *
     * @return bool
     * @throws PlatformRepositoryException
     */
    public function shouldAddGoDaddyBranding() : bool
    {
        if (PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->isReseller()) {
            return false;
        }

        $screen = WordPressRepository::getCurrentScreen();

        return $screen && in_array($screen->getPageId(), [
            'wc_voucher_template_list',
            'wc_voucher_list',
            'edit_wc_voucher',
            'add_wc_voucher',
            'admin_page_wc-pdf-product-vouchers-redeem-voucher',
        ], true);
    }

    /**
     * Broadcasts a FeatureEnabledEvent when the Gift Certificates feature is enabled for the first time.
     *
     * @internal
     *
     * @throws Exception
     */
    public function broadcastFeatureEnabledEvent()
    {
        Events::broadcast(new FeatureEnabledEvent('gift_certificates'));

        update_option($this->featureEnabledEventOptionName, 'no');
    }

    /**
     * Determines whether it should broadcast a FeatureEnabledEvent for the Gift Certificates feature.
     *
     * @internal
     *
     * @return bool
     */
    public function shouldBroadcastFeatureEnabledEvent() : bool
    {
        // try to limit processing to document requests initiated by a merchant on the admin dashboard
        if (WordPressRepository::isAjax() || ! current_user_can('manage_woocommerce')) {
            return false;
        }

        if (false !== get_option($this->featureEnabledEventOptionName, false)) {
            return false;
        }

        return true;
    }

    /**
     * Determines whether it should display a notice when the WP Cron is disabled.
     *
     * Do not show the notice when the hosting platform is MWP because it handles cron in its own way.
     *
     * @internal
     *
     * @param bool $wpCronDisabled
     * @return bool
     */
    public function shouldAddWpCronDisabledNotice(bool $wpCronDisabled) : bool
    {
        if (! $wpCronDisabled) {
            return false;
        }

        try {
            return ! ArrayHelper::contains(['mwp', 'woosaas'], PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->getPlatformName());
        } catch (PlatformRepositoryException $exception) {
            // do nothing, if we cannot determine the platform, we can assume it is not MWP
        }

        return true;
    }
}
