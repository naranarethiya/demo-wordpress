<?php

namespace GoDaddy\WordPress\MWC\Common\Features;

use GoDaddy\WordPress\MWC\Common\Components\Contracts\ConditionalComponentContract;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Platforms\Exceptions\PlatformRepositoryException;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;

/**
 * Abstract for feature classes.
 */
abstract class AbstractFeature implements ConditionalComponentContract
{
    /** @var int should never disable account restrictions */
    const DISABLE_ACCOUNT_RESTRICTION_NEVER = 0;

    /** @var int feature is not subject to account restrictions */
    const DISABLE_ACCOUNT_RESTRICTION_ALWAYS = 1;

    /** @var int should disable account restrictions based on config override */
    const DISABLE_ACCOUNT_RESTRICTION_WITH_OVERRIDE = 2;

    /**
     * Gets the feature name, matching the key used in configuration.
     *
     * @example 'email_notifications'
     *
     * @return string
     */
    abstract public static function getName() : string;

    /**
     * Initializes this feature.
     */
    abstract public function load();

    /**
     * Determines if this feature is enabled.
     *
     * @return bool
     */
    public static function isEnabled() : bool
    {
        if (static::getConfiguration('overrides.disabled', false)) {
            return false;
        }

        if (static::getConfiguration('overrides.enabled', false)) {
            return true;
        }

        return static::getConfiguration('enabled', true);
    }

    /**
     * Determines whether the class should load.
     *
     * @return bool
     */
    public static function shouldLoad() : bool
    {
        return static::shouldLoadFeature();
    }

    /**
     * Determines whether the feature should be loaded.
     *
     * @see AbstractFeature::shouldLoad()
     *
     * @return bool
     */
    protected static function shouldLoadFeature() : bool
    {
        return static::isEnabled()
            && static::isAccountAllowedToUseTheFeature()
            && static::isHostingPlanAllowed()
            && static::areRequiredPluginsActive()
            && static::areRequiredFeaturesAvailable();
    }

    /**
     * Determines the current hosting plan is allowed to use this feature.
     *
     * @return bool
     */
    protected static function isHostingPlanAllowed() : bool
    {
        $allowedHostingPlans = static::getConfiguration('allowedHostingPlans', []);

        // any plan allowed
        if (empty($allowedHostingPlans)) {
            return true;
        }

        try {
            return ArrayHelper::contains(
                $allowedHostingPlans,
                PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->getPlan()->getName()
            );
        } catch (PlatformRepositoryException $exception) {
            return false;
        }
    }

    /**
     * Determines if the plugins required to use this feature are active.
     *
     * @return bool True if the required plugins are active, otherwise false.
     */
    protected static function areRequiredPluginsActive() : bool
    {
        $requiredPlugins = ArrayHelper::wrap(static::getConfiguration('requiredPlugins', []));

        // Currently, this only supports a check against WooCommerce.
        if (ArrayHelper::contains($requiredPlugins, 'woocommerce')) {
            return WooCommerceRepository::isWooCommerceActive();
        }

        return true;
    }

    /**
     * Determines if the other features required to use this feature are available/loadable.
     *
     * @return bool
     */
    protected static function areRequiredFeaturesAvailable() : bool
    {
        $requiredFeatures = ArrayHelper::wrap(static::getConfiguration('requiredFeatures', []));

        foreach ($requiredFeatures as $featureClass) {
            if (! class_exists($featureClass)
                || ! method_exists($featureClass, 'shouldLoad')
                || ! $featureClass::shouldLoad()
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determines the account is allowed to use this feature.
     *
     * @return bool
     */
    protected static function isAccountAllowedToUseTheFeature() : bool
    {
        try {
            if (! PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->isReseller()) {
                return true;
            }
        } catch (PlatformRepositoryException $exception) {
            return false;
        }

        $disableAccountRestriction = static::getConfiguration('shouldDisableAccountRestriction', static::DISABLE_ACCOUNT_RESTRICTION_WITH_OVERRIDE);

        if ($disableAccountRestriction === static::DISABLE_ACCOUNT_RESTRICTION_NEVER) {
            return false;
        }

        if ($disableAccountRestriction === static::DISABLE_ACCOUNT_RESTRICTION_ALWAYS) {
            return true;
        }

        return ManagedWooCommerceRepository::isAllowedToUseNativeFeatures();
    }

    /**
     * Gets a configuration value for this feature.
     *
     * @param string $key dot notated array key for the feature sub-configuration
     * @param mixed $default default value to return
     * @return mixed|null
     */
    public static function getConfiguration(string $key, $default = null)
    {
        return Configuration::get(sprintf('features.%s.%s', static::getName(), $key), $default);
    }

    /**
     * Determines whether feature should be visible in the features section in the admin.
     *
     * @return bool
     */
    public static function shouldBeVisible() : bool
    {
        return static::shouldLoad();
    }
}
