<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\GoDaddy\Builders;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\LineItemOption;

/**
 * @extends AbstractDataObjectBuilder<LineItemOption>
 */
class LineItemOptionBuilder extends AbstractDataObjectBuilder
{
    /**
     * Creates a new {@see LineItemOption} data object using the current data as source.
     *
     * @return LineItemOption
     */
    public function build() : LineItemOption
    {
        return new LineItemOption([
            'attribute' => TypeHelper::string(ArrayHelper::get($this->data, 'attribute'), ''),
            'values'    => array_map(
                static fn ($value) => TypeHelper::string($value, ''),
                TypeHelper::array(ArrayHelper::get($this->data, 'values'), [])
            ),
        ]);
    }
}
