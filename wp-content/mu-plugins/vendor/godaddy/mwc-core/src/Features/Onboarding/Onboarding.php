<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Onboarding;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Components\Traits\HasComponentsTrait;
use GoDaddy\WordPress\MWC\Common\Features\AbstractFeature;
use GoDaddy\WordPress\MWC\Core\Features\Onboarding\API\API;
use GoDaddy\WordPress\MWC\Core\Features\Onboarding\WooCommerce\Overrides\Onboarding as WooCommerceOnboarding;
use GoDaddy\WordPress\MWC\Core\Features\Onboarding\WooCommerce\Overrides\Wizard;
use GoDaddy\WordPress\MWC\Core\Features\Onboarding\WordPress\Overrides as WordPressOverrides;

class Onboarding extends AbstractFeature
{
    use HasComponentsTrait;

    /** @var class-string<ComponentContract>[] alphabetically ordered list of components to load */
    protected $componentClasses = [
        API::class,
        Wizard::class,
        WooCommerceOnboarding::class,
        WordPressOverrides::class,
    ];

    /**
     * Gets the feature name, matching the key used in configuration.
     *
     * @return string
     */
    public static function getName() : string
    {
        return 'onboarding';
    }

    /**
     * Initializes the feature.
     *
     * @throws Exception
     */
    public function load()
    {
        $this->loadComponents();
    }

    /**
     * {@inheritDoc}
     */
    public static function shouldLoad() : bool
    {
        return static::userHasRequiredCapabilities() && static::shouldLoadFeature();
    }

    /**
     * Determines if the current user the necessary capabilities.
     *
     * @return bool
     */
    protected static function userHasRequiredCapabilities() : bool
    {
        return function_exists('current_user_can') &&
            current_user_can('manage_woocommerce') &&
            current_user_can('install_plugins') &&
            current_user_can('activate_plugins');
    }
}
