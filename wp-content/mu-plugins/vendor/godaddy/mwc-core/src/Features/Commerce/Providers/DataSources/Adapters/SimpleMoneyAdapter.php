<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataSources\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\SimpleMoney;

/**
 * Adapter for converting {@see CurrencyAmount} objects into {@see SimpleMoney} DTOs.
 */
class SimpleMoneyAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /**
     * Converts a {@see CurrencyAmount} into a {@see SimpleMoney} DTO.
     *
     * @param CurrencyAmount|null $currencyAmount
     * @return SimpleMoney|null
     */
    public function convertToSource(CurrencyAmount $currencyAmount = null) : ?SimpleMoney
    {
        if (! $currencyAmount) {
            return null;
        }

        return SimpleMoney::from($currencyAmount->getCurrencyCode(), $currencyAmount->getAmount());
    }

    /**
     * Converts a {@see CurrencyAmount} into a {@see SimpleMoney} DTO.
     *
     * Returns a {@see SimpleMoney} DTO with zero value if no currency amount is given.
     *
     * @param CurrencyAmount|null $currencyAmount
     * @return SimpleMoney
     */
    public function convertToSourceOrZero(?CurrencyAmount $currencyAmount) : SimpleMoney
    {
        return $this->convertToSource($currencyAmount) ??
            SimpleMoney::from(WooCommerceRepository::getCurrency(), 0);
    }

    /**
     * Converts a {@see SimpleMoney} DTO into a {@see CurrencyAmount} instance.
     *
     * @param SimpleMoney|null $source
     * @return CurrencyAmount|null
     */
    public function convertFromSource(SimpleMoney $source = null) : ?CurrencyAmount
    {
        if (! $source) {
            return null;
        }

        return $this->convertFromSimpleMoney($source);
    }

    /**
     * Converts a {@see SimpleMoney} DTO into a {@see CurrencyAmount} instance.
     *
     * @param SimpleMoney $source
     * @return CurrencyAmount
     */
    public function convertFromSimpleMoney(SimpleMoney $source) : CurrencyAmount
    {
        return CurrencyAmount::seed([
            'currencyCode' => $source->currencyCode,
            'amount'       => $source->value,
        ]);
    }
}
