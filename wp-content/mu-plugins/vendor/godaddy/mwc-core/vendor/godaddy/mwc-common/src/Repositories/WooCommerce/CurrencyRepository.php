<?php

namespace GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;

/**
 * Repository for handling WooCommerce amounts.
 */
class CurrencyRepository
{
    /**
     * Converts the string amount from WooCommerce to a integer in cents.
     *
     * @param string $amount
     * @return int
     */
    public static function getStripeAmount(string $amount) : int
    {
        if (ArrayHelper::contains(self::getNoDecimalCurrencies(), strtolower(get_woocommerce_currency()))) {
            return absint($amount);
        } else {
            return absint(wc_format_decimal(wc_add_number_precision((float) $amount), wc_get_price_decimals()));
        }
    }

    /**
     * Gets the number of decimal places for a given currency code.
     *
     * @param string $currencyCode
     * @return int
     */
    public static function getCurrencyDecimalPlaces(string $currencyCode) : int
    {
        $currencyCode = strtolower($currencyCode);

        if (ArrayHelper::contains(static::getNoDecimalCurrencies(), $currencyCode)) {
            return 0;
        } elseif (ArrayHelper::contains(static::getThreeDecimalCurrencies(), $currencyCode)) {
            return 3;
        }

        return 2;
    }

    /**
     * Returns a list of currencies that have zero decimal places.
     *
     * @return string[]
     */
    public static function getNoDecimalCurrencies() : array
    {
        return [
            'bif', // Burundian Franc
            'clp', // Chilean Peso
            'djf', // Djiboutian Franc
            'gnf', // Guinean Franc
            'jpy', // Japanese Yen
            'kmf', // Comorian Franc
            'krw', // South Korean Won
            'mga', // Malagasy Ariary
            'pyg', // Paraguayan Guaraní
            'rwf', // Rwandan Franc
            'ugx', // Ugandan Shilling
            'vnd', // Vietnamese Đồng
            'vuv', // Vanuatu Vatu
            'xaf', // Central African Cfa Franc
            'xof', // West African Cfa Franc
            'xpf', // Cfp Franc
        ];
    }

    /**
     * Returns a list of currencies that have three decimal places.
     *
     * @return string[]
     */
    public static function getThreeDecimalCurrencies() : array
    {
        return [
            'bhd', // Bahraini Dinar
            'iqd', // Iraqi Dinar
            'jod', // Jordanian Dinar
            'kwd', // Kwaiti Dinar
            'lyd', // Lybian Dinar
            'omr', // Rial Omani
            'tnd', // Tunisian Dinar
        ];
    }
}
