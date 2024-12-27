<?php

namespace GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\CurrencyRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

/**
 * Currency amount adapter.
 *
 * @method static static getNewInstance(float $amount, string $currency)
 */
class CurrencyAmountAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var float currency amount */
    private $amount;

    /** @var string currency code */
    private $currency;

    /**
     * Currency amount adapter constructor.
     *
     * @param float $amount
     * @param string $currency
     */
    public function __construct(float $amount, string $currency)
    {
        $this->amount = $amount;
        $this->currency = $currency;
    }

    /**
     * Converts a currency amount into a native object.
     *
     * @return CurrencyAmount
     */
    public function convertFromSource() : CurrencyAmount
    {
        $currencyAmount = new CurrencyAmount();

        return $currencyAmount
            ->setAmount((int) round($this->amount * $this->conversionFactor($this->currency)))
            ->setCurrencyCode($this->currency);
    }

    /**
     * Converts a currency amount to a float.
     *
     * @param CurrencyAmount|null $currencyAmount
     * @return float
     */
    public function convertToSource(?CurrencyAmount $currencyAmount = null) : float
    {
        if ($currencyAmount) {
            $this->amount = (float) ($currencyAmount->getAmount() / max(1, $this->conversionFactor($currencyAmount->getCurrencyCode())));
            $this->currency = $currencyAmount->getCurrencyCode();
        }

        return $this->amount;
    }

    /**
     * Get the conversion factor for a given currency.
     *
     * For decimal-based currencies converting to and from the smallest unit is accomplished by using a conversion factor of 100.
     * Some currencies do not use decimals and therefore do not need conversion.
     * A few have 3-decimal places, and require a conversion factor of 1000.
     *
     * @param string $currencyCode
     * @return int
     */
    protected function conversionFactor(string $currencyCode) : int
    {
        if (ArrayHelper::contains(CurrencyRepository::getNoDecimalCurrencies(), strtolower($currencyCode))) {
            return 1;
        }

        if (ArrayHelper::contains(CurrencyRepository::getThreeDecimalCurrencies(), strtolower($currencyCode))) {
            return 1000;
        }

        return 100;
    }
}
