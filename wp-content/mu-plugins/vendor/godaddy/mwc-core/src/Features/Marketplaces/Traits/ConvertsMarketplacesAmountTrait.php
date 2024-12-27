<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Traits;

use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\CurrencyAmountAdapter;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;

/**
 * A trait used in GDM adapters when an amount needs to be converted from source.
 */
trait ConvertsMarketplacesAmountTrait
{
    /** @var array Source data */
    protected $source;

    /**
     * Parses an amount value from the source and converts it.
     *
     * @param string $sourceKey
     * @return CurrencyAmount
     */
    protected function parseAndConvertAmountFromSource(string $sourceKey) : CurrencyAmount
    {
        return $this->adaptCurrencyAmount(
            (float) ArrayHelper::get($this->source, $sourceKey, 0.00)
        );
    }

    /**
     * Adapts the supplied amount.
     *
     * Note that the currency is not provided in the GDM payload, which is why we're using the store currency.
     *
     * @param float $amount
     * @return CurrencyAmount
     */
    protected function adaptCurrencyAmount(float $amount) : CurrencyAmount
    {
        return (new CurrencyAmountAdapter($amount, WooCommerceRepository::getCurrency()))->convertFromSource();
    }
}
