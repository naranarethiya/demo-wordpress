<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Handlers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notice;
use GoDaddy\WordPress\MWC\Common\Admin\Notices\Notices;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\ExtensionDeactivationFailedException;
use GoDaddy\WordPress\MWC\Common\Extensions\Types\PluginExtension;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\SiteRepository;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Pages\MarketplacesPage;

/**
 * The Sellbrite plugin handler.
 */
class SellbritePluginHandler implements ComponentContract
{
    /** @var string the community plugin name */
    protected $pluginName = 'sellbrite/sellbrite.php';

    /** @var string the community plugin slug */
    protected $pluginSlug = 'sellbrite';

    /** @var string flag used for when the Sellbrite plugin was automatically deactivated */
    protected $pluginDeactivationFlag = 'mwc_sellbrite_plugin_deactivated';

    /**
     * Loads the component and adds hooks.
     *
     * @throws Exception
     */
    public function load()
    {
        Register::action()
            ->setGroup('admin_init')
            ->setHandler([$this, 'maybeDeactivatePlugin'])
            ->execute();

        $this->enqueueNoticeUponPluginDeactivation();
    }

    /**
     * Gets the plugin instance.
     *
     * @return PluginExtension
     */
    protected function getSellbritePlugin() : PluginExtension
    {
        return (new PluginExtension())
            ->setName($this->pluginName)
            ->setBasename($this->pluginName)
            ->setSlug($this->pluginSlug);
    }

    /**
     * May deactivate the Sellbrite plugin.
     *
     * @internal
     *
     * @return void
     */
    public function maybeDeactivatePlugin() : void
    {
        $plugin = $this->getSellbritePlugin();

        try {
            if ($plugin->isActive()) {
                $plugin->deactivate();

                update_option($this->pluginDeactivationFlag, true);
            }
        } catch (ExtensionDeactivationFailedException $exception) {
            // catch the exception in callback context and try again on the next request
        }
    }

    /**
     * Displays a notice to admins if the Sellbrite plugin has been deactivated.
     *
     * @TODO this notice CTA must be updated with a working link to the Marketplaces page {unfulvio 2022-05-11}
     *
     * @internal
     *
     * @return void
     */
    protected function enqueueNoticeUponPluginDeactivation() : void
    {
        $marketplacesPageUrl = SiteRepository::getAdminUrl('admin.php?page='.MarketplacesPage::SLUG);

        ob_start(); ?>

        <p><?php esc_html_e('The Sellbrite plugin is included in your hosting plan as GoDaddy Marketplaces. The plugin has been deactivated.', 'mwc-core'); ?></p>
        <p><a href="<?php echo esc_url($marketplacesPageUrl); ?>" class="button button-primary"><?php esc_html_e('Set up', 'mwc-core'); ?></a></p><?php

        $content = ob_get_clean();

        $notice = (new Notice())
            ->setId($this->pluginDeactivationFlag)
            ->setType(Notice::TYPE_INFO)
            ->setRestrictedUserCapabilities(['manage_woocommerce'])
            ->setDismissible(true)
            ->setTitle(esc_html__('Your products, everywhere.', 'mwc-core'))
            ->setContent(TypeHelper::ensureString($content))
            ->setRenderCondition(function () {
                return ! empty(get_option($this->pluginDeactivationFlag));
            });

        Notices::enqueueAdminNotice($notice);
    }
}
