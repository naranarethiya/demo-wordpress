<?php

namespace GoDaddy\WordPress\MWC\Common\Repositories;

use Exception;
use GoDaddy\WordPress\MWC\Common\Cache\Cache;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\DeprecationHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Request;
use GoDaddy\WordPress\MWC\Common\Platforms\Contracts\PlatformRepositoryContract;
use GoDaddy\WordPress\MWC\Common\Platforms\Exceptions\PlatformRepositoryException;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformEnvironment;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;
use http\Url;

/**
 * Managed WooCommerce repository class.
 */
class ManagedWooCommerceRepository
{
    /**
     * Gets the current Managed WordPress environment.
     *
     * @return string
     */
    public static function getEnvironment() : string
    {
        if (Configuration::get('mwc.env')) {
            return TypeHelper::string(Configuration::get('mwc.env'), PlatformEnvironment::PRODUCTION);
        }

        try {
            $platformEnvironment = PlatformRepositoryFactory::getNewInstance()
                                                            ->getPlatformRepository()
                                                            ->getPlatformEnvironment()
                                                            ->getEnvironment();
        } catch (PlatformRepositoryException $exception) {
            // do nothing
        }

        $platformEnvironment = $platformEnvironment ?? PlatformEnvironment::PRODUCTION;

        Configuration::set('mwc.env', $platformEnvironment);

        return $platformEnvironment;
    }

    /**
     * Determines if the current is a production environment.
     *
     * @return bool
     */
    public static function isProductionEnvironment() : bool
    {
        return 'production' === static::getEnvironment();
    }

    /**
     * Determines if the current site environment is a staging environment.
     *
     * @return bool
     */
    public static function isStagingEnvironment() : bool
    {
        try {
            return PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->isStagingSite();
        } catch (PlatformRepositoryException $exception) {
            return false;
        }
    }

    /**
     * Determines if the current is a local environment.
     *
     * @return bool
     */
    public static function isLocalEnvironment() : bool
    {
        return 'development' === static::getEnvironment();
    }

    /**
     * Determines if the current is a testing environment.
     *
     * @return bool
     */
    public static function isTestingEnvironment() : bool
    {
        return 'testing' === static::getEnvironment();
    }

    /**
     * Determines whether the site can use native features.
     *
     * A site can use native features if it's not on a reseller account, or it's configured to allow native features for resellers.
     *
     * @return bool
     */
    public static function isAllowedToUseNativeFeatures() : bool
    {
        try {
            return ! PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->isReseller() || Configuration::get('mwc.allow_native_features_for_resellers');
        } catch (PlatformRepositoryException $exception) {
            return false;
        }
    }

    /**
     * Determines if the site is hosted on MWP and sold by a reseller with support agreement.
     *
     * @return bool
     */
    public static function isResellerWithSupportAgreement() : bool
    {
        try {
            if (! PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->isReseller()) {
                return false;
            }
        } catch (PlatformRepositoryException $exception) {
            return false;
        }

        return ! ArrayHelper::get(self::getResellerSettings(), 'customerSupportOptOut', true);
    }

    /**
     * Gets settings for a reseller account.
     *
     * @return array<string, mixed>
     */
    protected static function getResellerSettings() : array
    {
        try {
            $resellerId = PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->getResellerId();

            $request = (new Request())
                ->setUrl(StringHelper::trailingSlash(static::getStorefrontSettingsApiUrl()).$resellerId)
                ->setQuery(['fields' => 'customerSupportOptOut']);

            $response = Cache::httpResponse()
                ->setKeyFromRequest($request)
                ->expires(24 * 3600)
                ->remember(function () use ($request) {
                    return $request->send();
                });

            $settings = $response->getBody();
        } catch (Exception $e) {
            $settings = [];
        }

        return ArrayHelper::wrap($settings);
    }

    /**
     * Gets the Storefront Settings API URL.
     *
     * @return string
     */
    private static function getStorefrontSettingsApiUrl() : string
    {
        return StringHelper::trailingSlash(static::getApiUrl())
            .TypeHelper::string(Configuration::get('mwc.extensions.api.settings.reseller.endpoint', ''), '');
    }

    /**
     * Determines if the site used the WPNux template on-boarding system.
     *
     * @return bool
     */
    public static function hasCompletedWPNuxOnboarding() : bool
    {
        return WordPressRepository::hasWordPressInstance() && (bool) get_option('wpnux_imported');
    }

    /**
     * Returns an array with all activated features (name only).
     *
     * @return array
     */
    public static function getActiveFeatures() : array
    {
        $activeFeatures = [];

        foreach (Configuration::get('features') as $key => $value) {
            if (ArrayHelper::get($value, 'enabled')) {
                $activeFeatures[] = $key;
            }
        }

        return $activeFeatures;
    }

    /**
     * Gets the API URL.
     *
     * @return string URL
     */
    public static function getApiUrl() : string
    {
        $environment = static::getEnvironment();

        if (ArrayHelper::contains(['development', 'testing'], $environment)) {
            $apiUrl = Configuration::get('mwc.extensions.api.url.dev');
        } else {
            $apiUrl = Configuration::get('mwc.extensions.api.url.prod');
        }

        return TypeHelper::string($apiUrl, '');
    }

    /**
     * Determines if the site is hosted on MWP and is using a temporary domain.
     *
     * @deprecated Use {@see PlatformRepositoryContract::isTemporaryDomain()} instead (call the {@see PlatformRepositoryFactory::getPlatformRepository()} method to get the current platform repository)
     *
     * @return bool
     * @throws PlatformRepositoryException
     */
    public static function isTemporaryDomain() : bool
    {
        DeprecationHelper::deprecatedFunction(__METHOD__, '5.0.11', 'PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->isTemporaryDomain()');

        return PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->isTemporaryDomain();
    }

    /**
     * Determines if the site is hosted on Managed WordPress and has an eCommerce plan.
     *
     * @deprecated Use {@see PlatformRepositoryContract::hasEcommercePlan()} instead (call the {@see PlatformRepositoryFactory::getPlatformRepository()} method to get the current platform repository)
     *
     * @return bool
     * @throws PlatformRepositoryException
     */
    public static function hasEcommercePlan() : bool
    {
        DeprecationHelper::deprecatedFunction(__METHOD__, '5.0.11', 'PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->hasEcommercePlan()');

        return PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->hasEcommercePlan();
    }

    /**
     * Returns the customer ID.
     *
     * @deprecated Use {@see PlatformRepositoryContract::getGoDaddyCustomerId()} instead (call the {@see PlatformRepositoryFactory::getPlatformRepository()} method to get the current platform repository)
     *
     * @return string|null
     * @throws PlatformRepositoryException
     */
    public static function getCustomerId()
    {
        DeprecationHelper::deprecatedFunction(__METHOD__, '5.0.11', 'PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->getGoDaddyCustomerId()');

        return PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->getGoDaddyCustomerId();
    }

    /**
     * Gets the configured reseller account, if present.
     *
     * `1` means the site is not a reseller site, but sold directly by GoDaddy.
     *
     * @deprecated Use {@see PlatformRepositoryContract::getResellerId()} instead (call the {@see PlatformRepositoryFactory::getPlatformRepository()} method to get the current platform repository)
     *
     * @return int|null
     * @throws PlatformRepositoryException
     */
    public static function getResellerId()
    {
        DeprecationHelper::deprecatedFunction(__METHOD__, '5.0.11', 'PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->getResellerId()');

        return PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->getResellerId();
    }

    /**
     * Determines if the site is hosted on MWP and sold by a reseller.
     *
     * @deprecated Use {@see PlatformRepositoryContract::isReseller()} instead (call the {@see PlatformRepositoryFactory::getPlatformRepository()} method to get the current platform repository)
     *
     * @return bool
     * @throws PlatformRepositoryException
     */
    public static function isReseller() : bool
    {
        DeprecationHelper::deprecatedFunction(__METHOD__, '5.0.11', 'PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->isReseller()');

        return PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->isReseller();
    }

    /**
     * Returns the venture ID.
     *
     * @deprecated Use {@see PlatformRepositoryContract::getVentureId()} instead (call the {@see PlatformRepositoryFactory::getPlatformRepository()} method to get the current platform repository)
     *
     * @return string|null
     * @throws PlatformRepositoryException
     */
    public static function getVentureId()
    {
        DeprecationHelper::deprecatedFunction(__METHOD__, '5.0.11', 'PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->getVentureId()');

        return PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->getVentureId();
    }

    /**
     * Gets the value of the XID server variable.
     *
     * @deprecated Use {@see PlatformRepositoryContract::getPlatformSiteId()} instead (call the {@see PlatformRepositoryFactory::getPlatformRepository()} method to get the current platform repository)
     *
     * @return int
     * @throws PlatformRepositoryException
     */
    public static function getXid() : int
    {
        DeprecationHelper::deprecatedFunction(__METHOD__, '5.0.0', 'PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->getPlatformSiteId()');

        return (int) PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->getPlatformSiteId();
    }

    /**
     * Gets the ID for the site.
     *
     * @deprecated Use {@see PlatformRepositoryContract::getSiteId()} instead (call the {@see PlatformRepositoryFactory::getPlatformRepository()} method to get the current platform repository)
     *
     * @return string
     * @throws PlatformRepositoryException
     */
    public static function getSiteId() : string
    {
        DeprecationHelper::deprecatedFunction(__METHOD__, '5.0.0', 'PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->getSiteId()');

        return PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->getSiteId();
    }

    /**
     * Returns the name of the current platform.
     *
     * @deprecated Use {@see PlatformRepositoryContract::getPlatformName()} instead (call the {@see PlatformRepositoryFactory::getPlatformRepository()} method to get the current platform repository)
     *
     * @return string
     * @throws PlatformRepositoryException
     */
    public static function getPlatform() : string
    {
        DeprecationHelper::deprecatedFunction(__METHOD__, '5.0.0', 'PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->getPlatformName()');

        return PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->getPlatformName();
    }
}
