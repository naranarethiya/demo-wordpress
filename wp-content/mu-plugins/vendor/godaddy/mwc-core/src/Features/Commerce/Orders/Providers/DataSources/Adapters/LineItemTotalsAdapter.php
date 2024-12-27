<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\Adapters;

use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;
use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\LineItemTotals;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\Contracts\DataObjectAdapterContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\SimpleMoney;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataSources\Adapters\SimpleMoneyAdapter;

class LineItemTotalsAdapter implements DataObjectAdapterContract
{
    protected SimpleMoneyAdapter $simpleMoneyAdapter;

    /**
     * Constructor.
     *
     * @param SimpleMoneyAdapter $simpleMoneyAdapter
     */
    public function __construct(SimpleMoneyAdapter $simpleMoneyAdapter)
    {
        $this->simpleMoneyAdapter = $simpleMoneyAdapter;
    }

    /**
     * {@inheritDoc}
     * @param LineItemTotals $source
     * @param LineItem|null $lineItem
     * @return LineItem
     */
    public function convertFromSource($source, LineItem $lineItem = null) : LineItem
    {
        $lineItem = $lineItem ?? new LineItem();

        return $lineItem
            ->setSubTotalAmount($this->simpleMoneyAdapter->convertFromSimpleMoney($source->subTotal))
            ->setTaxAmount($this->simpleMoneyAdapter->convertFromSimpleMoney($source->taxTotal))
            ->setTotalAmount($this->calculateTotalFromSource($source));
    }

    /**
     * Calculates total amount from source.
     *
     * @param LineItemTotals $source
     * @return CurrencyAmount
     */
    protected function calculateTotalFromSource(LineItemTotals $source) : CurrencyAmount
    {
        return CurrencyAmount::seed([
            'currencyCode' => $source->subTotal->currencyCode,
            'amount'       => $source->discountTotal->value > 0 ?
                $source->subTotal->value - $source->discountTotal->value :
                $source->subTotal->value,
        ]);
    }

    /**
     * {@inheritDoc}
     * @param LineItem $target
     * @return LineItemTotals
     */
    public function convertToSource($target) : LineItemTotals
    {
        $subTotalAmount = $target->getSubTotalAmount();

        return new LineItemTotals([
            'subTotal'      => $this->simpleMoneyAdapter->convertToSourceOrZero($subTotalAmount),
            'taxTotal'      => $this->simpleMoneyAdapter->convertToSourceOrZero($target->getTaxAmount()),
            'discountTotal' => $this->simpleMoneyAdapter->convertToSourceOrZero($this->calculateDiscountFromLineItem($target)),
            'feeTotal'      => SimpleMoney::from($subTotalAmount->getCurrencyCode(), 0),
        ]);
    }

    /**
     * Calculates the discount amount for the given line item.
     * @param LineItem $lineItem The line item from which the discount should be retreived.
     *
     * @return CurrencyAmount The discount amount for the line item. Zero amount if line item is not discounted.
     */
    protected function calculateDiscountFromLineItem(LineItem $lineItem) : CurrencyAmount
    {
        $subTotal = $lineItem->getSubTotalAmount();

        return CurrencyAmount::seed([
            'currencyCode' => $subTotal->getCurrencyCode(),
            'amount'       => max($subTotal->getAmount() - $lineItem->getTotalAmount()->getAmount(), 0),
        ]);
    }
}
