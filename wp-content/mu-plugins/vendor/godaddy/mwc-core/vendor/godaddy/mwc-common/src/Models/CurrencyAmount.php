<?php

namespace GoDaddy\WordPress\MWC\Common\Models;

use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\CurrencyRepository;

/**
 * An object representation of a currency amount.
 */
class CurrencyAmount extends AbstractModel
{
    /** @var int|null amount in cents */
    protected $amount;

    /** @var string|null 2-letter Unicode CLDR currency code */
    protected $currencyCode;

    /**
     * Gets the amount.
     *
     * @return int cents
     */
    public function getAmount() : int
    {
        return is_int($this->amount) ? $this->amount : 0;
    }

    /**
     * Gets the currency code.
     *
     * @return string 3-letter Unicode CLDR currency code
     */
    public function getCurrencyCode() : string
    {
        return is_string($this->currencyCode) ? $this->currencyCode : '';
    }

    /**
     * Sets the amount in cents.
     *
     * @param int $amount
     * @return $this
     */
    public function setAmount(int $amount) : CurrencyAmount
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Sets the currency code.
     *
     * @param string $code 3-letter Unicode CLDR currency code
     * @return $this
     */
    public function setCurrencyCode(string $code) : CurrencyAmount
    {
        $this->currencyCode = $code;

        return $this;
    }

    /**
     * Returns a numerical string representation of the amount, according to the configured number of decimals.
     *
     * @return string
     */
    public function toString() : string
    {
        $intAmount = $this->getAmount();
        $precision = pow(10, CurrencyRepository::getCurrencyDecimalPlaces($this->getCurrencyCode()));
        $floatAmount = $intAmount / max(1, $precision);
        $decimals = wc_get_price_decimals();

        return number_format($floatAmount, $decimals, '.', '');
    }

    /**
     * Returns a formatted string with the currency symbol and amount, in either HTML or plain text format.
     *
     * @param bool $preserveHtmlTags whether to preserve HTML tags (default false)
     * @return string
     */
    public function toFormattedString(bool $preserveHtmlTags = false) : string
    {
        $formattedString = wc_price((float) $this->toString(), ['currency' => $this->getCurrencyCode()]);

        return $preserveHtmlTags ? $formattedString : strip_tags($formattedString);
    }
}
