<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Stripe\Locales;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;

/**
 * Utility class for holding Stripe supported data and related validation.
 */
class LocalHelper
{
    /** @var array Stripe supported countries */
    protected static $supportedCountries = [
        'AU',
        'AT',
        'BE',
        'BR',
        'BG',
        'CA',
        'HR',
        'CY',
        'CZ',
        'DK',
        'EE',
        'FI',
        'FR',
        'DE',
        'GI',
        'GR',
        'HK',
        'HU',
        'IN',
        'IE',
        'IT',
        'JP',
        'LV',
        'LI',
        'LT',
        'LU',
        'MY',
        'MT',
        'MX',
        'NL',
        'NZ',
        'NO',
        'PL',
        'PT',
        'RO',
        'SG',
        'SK',
        'SI',
        'ES',
        'SE',
        'CH',
        'AE',
        'GB',
        'US',
    ];

    /** @var array Stripe base supported currencies */
    protected static $supportedBaseCurrencies = [
        'USD',
        'AED',
        'AFN',
        'ALL',
        'AMD',
        'ANG',
        'AOA',
        'ARS',
        'AUD',
        'AWG',
        'AZN',
        'BAM',
        'BBD',
        'BDT',
        'BGN',
        'BIF',
        'BMD',
        'BND',
        'BOB',
        'BRL',
        'BSD',
        'BWP',
        'BYN',
        'BZD',
        'CAD',
        'CDF',
        'CHF',
        'CLP',
        'CNY',
        'COP',
        'CRC',
        'CVE',
        'CZK',
        'DJF',
        'DKK',
        'DOP',
        'DZD',
        'EGP',
        'ETB',
        'EUR',
        'FJD',
        'FKP',
        'GBP',
        'GEL',
        'GIP',
        'GMD',
        'GNF',
        'GTQ',
        'GYD',
        'HKD',
        'HNL',
        'HRK',
        'HTG',
        'HUF',
        'IDR',
        'ILS',
        'INR',
        'ISK',
        'JMD',
        'JPY',
        'KES',
        'KGS',
        'KHR',
        'KMF',
        'KRW',
        'KYD',
        'KZT',
        'LAK',
        'LBP',
        'LKR',
        'LRD',
        'LSL',
        'MAD',
        'MDL',
        'MGA',
        'MKD',
        'MMK',
        'MNT',
        'MOP',
        'MRO',
        'MUR',
        'MVR',
        'MWK',
        'MXN',
        'MYR',
        'MZN',
        'NAD',
        'NGN',
        'NIO',
        'NOK',
        'NPR',
        'NZD',
        'PAB',
        'PEN',
        'PGK',
        'PHP',
        'PKR',
        'PLN',
        'PYG',
        'QAR',
        'RON',
        'RSD',
        'RUB',
        'RWF',
        'SAR',
        'SBD',
        'SCR',
        'SEK',
        'SGD',
        'SHP',
        'SLL',
        'SOS',
        'SRD',
        'STD',
        'SZL',
        'THB',
        'TJS',
        'TOP',
        'TRY',
        'TTD',
        'TWD',
        'TZS',
        'UAH',
        'UGX',
        'UYU',
        'UZS',
        'VND',
        'VUV',
        'WST',
        'XAF',
        'XCD',
        'XOF',
        'XPF',
        'YER',
        'ZAR',
        'ZMW',
    ];

    /** @var array Stripe United Arab Emirates supported currencies */
    protected static $supportedUAECurrencies = [
        'BHD',
        'JOD',
        'KWD',
        'OMR',
        'TND',
    ];

    /**
     * Checks if configured WooCommerce country is a supported Stripe country.
     *
     * @param string $country
     * @return bool
     */
    public static function isSupportedCountry(string $country) : bool
    {
        return ArrayHelper::contains(static::$supportedCountries, $country);
    }

    /**
     * Checks if configured WooCommerce currency is a supported Stripe currency.
     *
     * @param string $country
     * @param string $currency
     * @return bool
     * @throws BaseException
     */
    public static function isSupportedCurrency(string $country, string $currency) : bool
    {
        if ($country !== 'AE') {
            return ArrayHelper::contains(static::$supportedBaseCurrencies, $currency);
        }

        return ArrayHelper::contains(ArrayHelper::combine(static::$supportedBaseCurrencies, static::$supportedUAECurrencies), $currency);
    }
}
