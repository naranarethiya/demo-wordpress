<?php

namespace GoDaddy\WordPress\MWC\Core\Features\PluginControls;

use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Components\Exceptions\ComponentClassesNotDefinedException;
use GoDaddy\WordPress\MWC\Common\Components\Exceptions\ComponentLoadFailedException;
use GoDaddy\WordPress\MWC\Common\Components\Traits\HasComponentsTrait;
use GoDaddy\WordPress\MWC\Common\Features\AbstractFeature;
use GoDaddy\WordPress\MWC\Core\Features\PluginControls\Interceptors\DisablePluginActionsInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\PluginControls\Interceptors\DisableUploadsForTrialsInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\PluginControls\Interceptors\RemovePluginActionLinksInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\PluginControls\Interceptors\RemovePluginsFromUpdatesListInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\PluginControls\Interceptors\UpdatesInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\PluginControls\Interceptors\WooCommerceUpdatesInterceptor;

/**
 * This feature adds functionality that controls various plugin behaviours and management operations, such as:.
 *
 * - Whether plugins can be installed or uninstalled.
 * - Whether plugins can be updated by the merchant.
 */
class PluginControls extends AbstractFeature
{
    use HasComponentsTrait;

    /** @var array<class-string<ComponentContract>> */
    protected array $componentClasses = [
        DisablePluginActionsInterceptor::class,
        DisableUploadsForTrialsInterceptor::class,
        RemovePluginActionLinksInterceptor::class,
        RemovePluginsFromUpdatesListInterceptor::class,
        UpdatesInterceptor::class,
        WooCommerceUpdatesInterceptor::class,
    ];

    /**
     * {@inheritDoc}
     */
    public static function getName() : string
    {
        return 'plugin_controls';
    }

    /**
     * Initializes the components.
     *
     * @return void
     * @throws ComponentClassesNotDefinedException
     * @throws ComponentLoadFailedException
     */
    public function load() : void
    {
        $this->loadComponents();
    }
}
