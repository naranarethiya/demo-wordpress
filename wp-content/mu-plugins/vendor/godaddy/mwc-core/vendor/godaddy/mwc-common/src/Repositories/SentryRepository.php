<?php

namespace GoDaddy\WordPress\MWC\Common\Repositories;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Platforms\Contracts\PlatformRepositoryContract;
use GoDaddy\WordPress\MWC\Common\Platforms\Exceptions\PlatformRepositoryException;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;
use Jean85\PrettyVersions;
use function Sentry\configureScope;
use Sentry\Event as SentryEvent;
use function Sentry\init as InitializeSentry;
use Sentry\State\Scope;
use Throwable;

class SentryRepository
{
    /**
     * Retrieves the current WooCommerce access token.
     */
    public static function initialize() : void
    {
        if (! ($dsn = Configuration::get('reporting.sentry.dsn')) || ! Configuration::get('reporting.sentry.enabled') || ! static::loadSDK()) {
            return;
        }

        $currentEnv = ManagedWooCommerceRepository::getEnvironment();

        if (! ArrayHelper::contains(['development', 'staging', 'production'], $currentEnv)) {
            return;
        }

        InitializeSentry([
            'dsn'             => $dsn,
            'environment'     => $currentEnv,
            'max_breadcrumbs' => 50, // Amount of trace breadcrumbs -- default is 100
            'release'         => Configuration::get('mwc.version'), // @TODO: Replace version with commit hash {JO 2021-03-03}
            'sample_rate'     => 1.0, // Keep at 100%. {@see SentrySampleRateRepository} for overrides.
            'before_send'     => [static::class, 'beforeSend'],
        ]);

        // Set scopes
        static::configureSentryScopes();
    }

    /**
     * Checks if can retrieve Sentry version the way Sentry would.
     *
     * This is the way Sentry checks internally and can sometimes fail so we want to bail here if it fails
     * to avoid a 500 error on a customer site.
     *
     * @return bool
     */
    public static function canGetSentryVersion() : bool
    {
        try {
            PrettyVersions::getVersion('sentry/sentry');
        } catch (Throwable $throwable) {
            return false;
        }

        return true;
    }

    /**
     * Checks whether the event should be sent to sentry. Applies sample rate logic.
     *
     * @param SentryEvent $event
     *
     * @return SentryEvent|null
     */
    public static function beforeSend(SentryEvent $event) : ?SentryEvent
    {
        if (static::hasSentryException($event) && SentrySampleRateRepository::getInstance()->canSampleEvent($event)) {
            return $event;
        }

        return null;
    }

    /**
     * Checks if exception is explicitly declared as a reportable Sentry Exception.
     *
     * @NOTE If exceptions do not extend the base sentry exception they are not considered reportable.
     *
     * @param SentryEvent $event
     * @return bool
     */
    protected static function hasSentryException(SentryEvent $event) : bool
    {
        foreach (ArrayHelper::wrap($event->getExceptions()) as $exceptionBag) {
            // @NOTE: Only send Exceptions intended for Sentry {JO 2021-03-03}
            if (is_a($exceptionBag->getType(), SentryException::class, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if the server instance meets the system requirements for Sentry.
     *
     * @NOTE: The sentry SDK Require PHP 7.2 or higher so we should check before loading any classes.
     *
     * @return bool
     */
    public static function hasSystemRequirements() : bool
    {
        return version_compare(PHP_VERSION, '7.2.0') >= 0;
    }

    /**
     * Configure the sentry scopes for tagging.
     */
    protected static function configureSentryScopes() : void
    {
        if (! static::loadSDK()) {
            return;
        }

        configureScope([static::class, 'setSentryScopes']);
    }

    /**
     * Set sentry scope user, tags, etc.
     *
     * @note needs to be public because sentry calls it from outside of class.
     * @param Scope $scope
     */
    public static function setSentryScopes(Scope $scope) : void
    {
        // Use domain as unique identifier
        $scope->setUser(['id' => ArrayHelper::get($_SERVER, 'HTTP_HOST', '')]);
        $scope->setTag('account_plan', static::getAccountPlanName());
        $scope->setTag('cdn_enabled', Configuration::get('godaddy.cdn.enabled') ? 'yes' : 'no');
        $scope->setTag('request_type', TypeHelper::string(Configuration::get('mwc.mode', 'web'), ''));
        $scope->setTag('managed_woocommerce_version', TypeHelper::string(Configuration::get('mwc.version'), ''));
        $scope->setTag('woocommerce_active', WooCommerceRepository::isWooCommerceActive() ? 'yes' : 'no');
        $scope->setTag('woocommerce_version', TypeHelper::string(Configuration::get('woocommerce.version'), ''));
        $scope->setTag('wordpress_version', WordPressRepository::getVersion() ?? '');
        $scope->setTag('wordpress_cli_mode', WordPressRepository::isCliMode() ? 'yes' : 'no');
        $scope->setTags(static::getHostingPlatformTags());
    }

    /**
     * Gets the name of the hosting plan associated with the current site.
     *
     * @return string
     */
    protected static function getAccountPlanName() : string
    {
        $platformRepository = static::getPlatformRepository();

        return $platformRepository ? $platformRepository->getPlan()->getName() : '';
    }

    /**
     * Attempts to get an instance of {@see PlatformRepositoryContract}.
     *
     * @return PlatformRepositoryContract|null
     */
    protected static function getPlatformRepository() : ?PlatformRepositoryContract
    {
        try {
            return PlatformRepositoryFactory::getNewInstance()->getPlatformRepository();
        } catch (PlatformRepositoryException $e) {
            return null;
        }
    }

    /**
     * Gets the hosting platform data to use in the Sentry tags.
     *
     * @return string[]
     */
    protected static function getHostingPlatformTags() : array
    {
        $hostingPlatform = 'unknown';
        $isTemporaryDomain = false;

        try {
            $platformRepository = PlatformRepositoryFactory::getNewInstance()->getPlatformRepository();
            if ($platformRepository->hasPlatformData()) {
                $hostingPlatform = $platformRepository->getPlatformName();
            }
            $isTemporaryDomain = $platformRepository->isTemporaryDomain();
        } catch (PlatformRepositoryException $e) {
            // platform name will just use the default set above
        }

        return [
            'hosting_platform' => $hostingPlatform,
            'temporary_domain' => $isTemporaryDomain ? 'yes' : 'no',
        ];
    }

    /**
     * Loads sentry SDK entry point file if system requirements are met.
     *
     * @return bool
     */
    public static function loadSDK() : bool
    {
        // system requirements aren't met
        if (! static::hasSystemRequirements()) {
            return false;
        }

        // TODO: stop manually including the functions.php file when the minimum required PHP version for mwc-core is PHP 7.1 {wvega 2021-07-05}
        $path = static::getSentryFunctionsPath();

        if (! file_exists($path)) {
            return false;
        }

        // already loaded
        if (static::sentryLoaded()) {
            return true;
        }

        require_once $path;

        // @NOTE: If we still can't get version like Sentry package then bail {JO: 2021-08-12}
        if (! static::canGetSentryVersion()) {
            return false;
        }

        return true;
    }

    /**
     * Checks if Sentry SDK loaded or not.
     *
     * @return bool
     */
    public static function sentryLoaded() : bool
    {
        return static::canGetSentryVersion() && function_exists('Sentry\init');
    }

    /**
     * Gets the path to the functions.php included in the sentry package.
     *
     * @return string
     */
    protected static function getSentryFunctionsPath() : string
    {
        // path from vendor/godaddy/mwc-common/src/Repositories to vendor/sentry/sentry/src/functions.php
        return __DIR__.'/../../../../sentry/sentry/src/functions.php';
    }
}
