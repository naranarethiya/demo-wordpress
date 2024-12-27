<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Components\Exceptions\ComponentClassesNotDefinedException;
use GoDaddy\WordPress\MWC\Common\Components\Exceptions\ComponentLoadFailedException;
use GoDaddy\WordPress\MWC\Common\Components\Traits\HasComponentsTrait;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Features\AbstractFeature;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Interceptors\EnqueueApplePayNoticeInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Interceptors\EnqueueBusinessStatusNoticeInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Interceptors\EnqueueCompleteProfileNoticeInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Interceptors\EnqueueGdpNoticeInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Interceptors\EnqueueGdpRegisterRecommendationNoticeInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Interceptors\EnqueueGdpSipRecommendationNoticeInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Interceptors\EnqueueGooglePayNoticeInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Interceptors\EnqueueOnboardingErrorNoticeInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\GoDaddyPayments\Interceptors\EnqueuePoyntPluginNoticeInterceptor;

/**
 * The GoDaddy Payments feature.
 */
class GoDaddyPayments extends AbstractFeature
{
    use HasComponentsTrait;

    /** @var class-string<ComponentContract>[] alphabetically ordered list of components to load */
    protected array $componentClasses = [
        EnqueueApplePayNoticeInterceptor::class,
        EnqueueBusinessStatusNoticeInterceptor::class,
        EnqueueCompleteProfileNoticeInterceptor::class,
        EnqueueGooglePayNoticeInterceptor::class,
        EnqueueOnboardingErrorNoticeInterceptor::class,
        EnqueuePoyntPluginNoticeInterceptor::class,
        EnqueueGdpNoticeInterceptor::class,
        EnqueueGdpRegisterRecommendationNoticeInterceptor::class,
        EnqueueGdpSipRecommendationNoticeInterceptor::class,
        //        EnqueueWooStagingNoticeInterceptor::class,
    ];

    /**
     * {@inheritDoc}
     */
    public static function getName() : string
    {
        return 'godaddy_payments';
    }

    /**
     * Gets the list of supported countries.
     *
     * @return string[] array of ISO 3166-1 alpha-2 country codes
     */
    public static function getSupportedCountries() : array
    {
        return array_keys(TypeHelper::array(Configuration::get('features.godaddy_payments.supportedCountries'), []));
    }

    /**
     * Gets the list of supported currencies.
     *
     * When a country is provided, only the currencies supported by that country are returned.
     *
     * @param string|null $country
     * @return string[] array of ISO 3166-1 alpha-2 country codes
     */
    public static function getSupportedCurrencies(?string $country = null) : array
    {
        $currenciesByCountry = TypeHelper::array(Configuration::get('features.godaddy_payments.supportedCountries'), []);

        return $country ? (array) ($currenciesByCountry[$country] ?? []) : array_unique(ArrayHelper::flatten($currenciesByCountry));
    }

    /**
     * Checks whether the given country is supported or not.
     *
     * @param string $country
     * @return bool
     */
    public static function isSupportedCountry(string $country) : bool
    {
        return in_array($country, static::getSupportedCountries(), true);
    }

    /**
     * Checks whether the given currency is supported or not.
     *
     * When a country is provided, only the currencies supported by that country are checked.
     *
     * @param string $currency
     * @param string|null $country
     * @return bool
     */
    public static function isSupportedCurrency(string $currency, string $country = null) : bool
    {
        return in_array($currency, static::getSupportedCurrencies($country), true);
    }

    /**
     * Gets the Terms of Service URL, based on current locale or store country.
     *
     * @return string
     */
    public static function getTermsOfServiceUrl() : string
    {
        $locale = WordPressRepository::getLocale();
        $country = WooCommerceRepository::getBaseCountry();

        if ($locale === 'fr_CA' || ($country === 'CA' && StringHelper::startsWith($locale, 'fr'))) {
            return 'https://www.godaddy.com/fr-ca/legal/agreements/commerce-services-agreement';
        }

        if ($locale === 'en_CA' || $country === 'CA') {
            return 'https://www.godaddy.com/en-ca/legal/agreements/commerce-services-agreement';
        }

        return 'https://www.godaddy.com/legal/agreements/commerce-services-agreement';
    }

    /**
     * Determines if a shop is eligible for GDP.
     *
     * @return bool
     * @throws Exception
     */
    public static function isSiteEligible() : bool
    {
        if (PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->isReseller()) {
            return false;
        }

        return self::isEligibleCountryAndCurrency();
    }

    /**
     * Determines whether country + currency are eligible for GDP.
     *
     * @return bool
     */
    public static function isEligibleCountryAndCurrency() : bool
    {
        $country = WooCommerceRepository::getBaseCountry();
        $currency = WooCommerceRepository::getCurrency();

        return GoDaddyPayments::isSupportedCountry($country) && GoDaddyPayments::isSupportedCurrency($currency, $country);
    }

    /**
     * {@inheritDoc}
     *
     * @throws ComponentClassesNotDefinedException|ComponentLoadFailedException
     */
    public function load() : void
    {
        $this->loadComponents();
    }
}
