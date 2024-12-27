<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\GoDaddy\Builders;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\LineItemTotals;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\GoDaddy\Builders\Traits\CanBuildSimpleMoneyTrait;

/**
 * @extends AbstractDataObjectBuilder<LineItemTotals>
 */
class LineItemTotalsBuilder extends AbstractDataObjectBuilder
{
    use CanBuildSimpleMoneyTrait;

    /**
     * {@inheritDoc}
     */
    public function build() : LineItemTotals
    {
        return new LineItemTotals([
            'discountTotal' => $this->buildSimpleMoney(TypeHelper::array(ArrayHelper::get($this->data, 'discountTotal'), [])),
            'feeTotal'      => $this->buildSimpleMoney(TypeHelper::array(ArrayHelper::get($this->data, 'feeTotal'), [])),
            'subTotal'      => $this->buildSimpleMoney(TypeHelper::array(ArrayHelper::get($this->data, 'subTotal'), [])),
            'taxTotal'      => $this->buildSimpleMoney(TypeHelper::array(ArrayHelper::get($this->data, 'taxTotal'), [])),
        ]);
    }
}
