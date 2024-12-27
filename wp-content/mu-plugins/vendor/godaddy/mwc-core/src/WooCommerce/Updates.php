<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Enqueue\Enqueue;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Platforms\Exceptions\PlatformRepositoryException;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedExtensionsRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Common\Traits\Features\IsConditionalFeatureTrait;
use stdClass;

/**
 * Class Updates.
 */
class Updates
{
    use IsConditionalFeatureTrait;

    protected bool $hasManagedPlugins;
    protected bool $hasManagedThemes;

    /**
     * Class constructor.
     *
     * @throws PlatformRepositoryException|Exception
     */
    public function __construct()
    {
        $this->initializeProperties();
        $this->addHooks();
    }

    /**
     * Initializes the properties.
     *
     * @return void
     * @throws Exception
     */
    protected function initializeProperties() : void
    {
        $this->hasManagedPlugins = ! empty(ManagedExtensionsRepository::getManagedPlugins());
        $this->hasManagedThemes = ! empty(ManagedExtensionsRepository::getManagedThemes());
    }

    /**
     * Adds the necessary hooks.
     *
     * @return void
     * @throws PlatformRepositoryException|Exception
     */
    protected function addHooks() : void
    {
        $this->addEcommercePlanHooks();
        $this->addManagedPluginsHooks();
        $this->addManagedThemesHooks();
    }

    /**
     * Adds hooks for the Ecommerce plan.
     *
     * @return void
     * @throws PlatformRepositoryException|Exception
     */
    protected function addEcommercePlanHooks() : void
    {
        if (! PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->hasEcommercePlan()) {
            return;
        }

        if ($this->hasManagedPlugins) {
            Register::action()
                ->setGroup('admin_print_scripts')
                ->setHandler([$this, 'hideWooExtensionDetailsLinks'])
                ->setPriority(PHP_INT_MAX)
                ->execute();
        }
    }

    /**
     * Adds hooks for the managed plugins logic.
     *
     * @return void
     * @throws Exception
     */
    protected function addManagedPluginsHooks() : void
    {
        if (! $this->hasManagedPlugins) {
            return;
        }

        Register::filter()
            ->setGroup('pre_set_site_transient_update_plugins')
            ->setHandler([$this, 'addPluginExtensionsToUpdatesList'])
            ->setPriority(PHP_INT_MAX)
            ->execute();

        Register::filter()
            ->setGroup('upgrader_package_options')
            ->setHandler([$this, 'setPluginExtensionPackage'])
            ->setPriority(PHP_INT_MAX)
            ->execute();
    }

    /**
     * Adds hooks for the managed themes logic.
     *
     * @return void
     * @throws Exception
     */
    protected function addManagedThemesHooks() : void
    {
        if (! $this->hasManagedThemes) {
            return;
        }

        Register::filter()
            ->setGroup('pre_set_site_transient_update_themes')
            ->setHandler([$this, 'addThemeExtensionsToUpdatesList'])
            ->setPriority(PHP_INT_MAX)
            ->execute();
    }

    /**
     * Intercepts the plugins update transient to inject self-served plugins.
     *
     * @filter pre_set_site_transient_update_themes - PHP_INT_MAX
     *
     * @param mixed $list
     *
     * @return mixed|stdClass
     * @throws Exception
     */
    public function addPluginExtensionsToUpdatesList($list)
    {
        if (! $this->isUpdateListValid($list)) {
            return $list;
        }

        foreach (ManagedExtensionsRepository::getInstalledManagedPlugins() as $plugin) {
            if (ArrayHelper::has($list->checked, TypeHelper::string($plugin->getBasename(), ''))) {
                $itemVersion = $list->checked[$plugin->getBasename()];
                if ($itemVersion && version_compare($itemVersion, $plugin->getVersion(), '<')) {
                    // @TODO: Should be removed in favor of ->toArray from the CanConvertToArrayTrait when parity confirmed {JO: 2021-02-21}
                    $list->response[$plugin->getBasename()] = (object) [
                        'id'            => "w.org/plugins/{$plugin->getSlug()}",
                        'slug'          => $plugin->getSlug(),
                        'plugin'        => $plugin->getBasename(),
                        'new_version'   => $plugin->getVersion(),
                        'url'           => $plugin->getHomepageUrl(),
                        'package'       => $plugin->getPackageUrl(),
                        'icons'         => $plugin->getImageUrls(),
                        'banners'       => [],
                        'banners_rtl'   => [],
                        'tested'        => StringHelper::beforeLast(Configuration::get('wordpress.version') ?? '', '-beta'),
                        'requires_php'  => '',
                        'compatibility' => new stdClass(),
                    ];
                }
            }
        }

        return $list;
    }

    /**
     * Intercepts the plugin update package options to inject correct package url before downloading an update.
     *
     * @filter upgrader_package_options - PHP_INT_MAX
     *
     * @param mixed|array $options
     * @return mixed|array
     * @throws Exception
     */
    public function setPluginExtensionPackage($options)
    {
        $plugin = ArrayHelper::get($options, 'hook_extra.plugin');

        if (! $plugin) {
            return $options;
        }

        if ($managedPlugin = ManagedExtensionsRepository::getInstalledManagedPlugin($plugin)) {
            $options['package'] = $managedPlugin->getPackageUrl();
        }

        return $options;
    }

    /**
     * Intercepts the transient that holds available theme updates.
     *
     * @filter pre_set_site_transient_update_themes - PHP_INT_MAX
     *
     * @param mixed $list
     *
     * @return mixed|stdClass
     * @throws Exception
     */
    public function addThemeExtensionsToUpdatesList($list)
    {
        if (! $this->isUpdateListValid($list)) {
            return $list;
        }

        foreach (ManagedExtensionsRepository::getInstalledManagedThemes() as $theme) {
            $itemVersion = $list->checked[$theme->getSlug()];

            if ($itemVersion && version_compare($itemVersion, $theme->getVersion(), '<')) {
                // @TODO: Should be removed in favor of ->toArray from the CanConvertToArrayTrait when parity confirmed {JO: 2021-02-21}
                $list->response[$theme->getSlug()] = [
                    'download_link'         => $theme->getPackageUrl(),
                    'homepage'              => $theme->getHomepageUrl(),
                    'icons'                 => $theme->getImageUrls(),
                    'last_updated'          => date('Y-m-d', (int) $theme->getLastUpdated()),
                    'name'                  => $theme->getName(),
                    'short_description'     => $theme->getShortDescription(),
                    'slug'                  => $theme->getSlug(),
                    'support_documentation' => $theme->getDocumentationUrl(),
                    'type'                  => $theme->getType(),
                    'version'               => $theme->getInstalledVersion(),
                    'new_version'           => $theme->getVersion(),
                    'url'                   => $theme->getHomepageUrl(),
                    'package'               => $theme->getPackageUrl(),
                ];
            }
        }

        return $list;
    }

    /**
     * Checks if a given update list is valid.
     *
     * @param mixed $list
     *
     * @return bool
     */
    private function isUpdateListValid($list) : bool
    {
        if (! is_object($list) || ! property_exists($list, 'checked') || ! ArrayHelper::accessible($list->checked)) {
            return false;
        }

        return true;
    }

    /**
     * Hides the WooCommerce extension view details links.
     *
     * @internal
     *
     * @throws Exception
     */
    public function hideWooExtensionDetailsLinks()
    {
        if (! WordPressRepository::isCurrentScreen(['update-core', 'plugins'])) {
            return;
        }

        $names = [];
        $styles = '';

        foreach (ManagedExtensionsRepository::getInstalledManagedPlugins() as $plugin) {
            $names[] = $plugin->getBasename();
            $styles .= sprintf("a[href*='%s']{display: none;}", esc_attr($plugin->getSlug()));
        }

        if (WordPressRepository::isCurrentScreen('update-core')) {
            ?>
            <style type="text/css"><?php echo wp_kses_post($styles); ?></style><?php
        }

        if (WordPressRepository::isCurrentScreen('plugins')) {
            Enqueue::script()
                ->setHandle('remove-extensions-details')
                ->setSource(WordPressRepository::getAssetsUrl('js/hide-extensions-details.js'))
                ->setDeferred(true)
                ->attachInlineScriptObject('MWCExtensionsHideDetails')
                ->attachInlineScriptVariables(['names' => $names])
                ->execute();
        }
    }

    /**
     * Determines whether the feature should be loaded.
     *
     * @return bool
     * @throws PlatformRepositoryException
     */
    public static function shouldLoadConditionalFeature() : bool
    {
        return WooCommerceRepository::isWooCommerceActive()
            && PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->hasEcommercePlan();
    }
}
