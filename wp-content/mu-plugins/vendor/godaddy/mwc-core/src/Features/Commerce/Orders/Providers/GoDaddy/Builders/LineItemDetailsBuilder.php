<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\GoDaddy\Builders;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\LineItemDetails;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\LineItemOption;

/**
 * @extends AbstractDataObjectBuilder<LineItemDetails>
 * @property array<string, mixed> $data
 */
class LineItemDetailsBuilder extends AbstractDataObjectBuilder
{
    /**
     * Creates a new {@see LineItemDetails} data object using the current data as source.
     */
    public function build() : ?LineItemDetails
    {
        if (empty($this->data)) {
            return null;
        }

        $selectedOptionsData = ArrayHelper::getArrayValueForKey($this->data, 'selectedOptions');

        $properties = [
            'sku'             => TypeHelper::nonEmptyStringOrNull(ArrayHelper::get($this->data, 'sku')),
            'productAssetUrl' => TypeHelper::string(ArrayHelper::get($this->data, 'productAssetUrl'), ''),
            'unitOfMeasure'   => TypeHelper::nonEmptyStringOrNull(ArrayHelper::get($this->data, 'unitOfMeasure')),
            'selectedOptions' => $this->buildSelectedOptions($selectedOptionsData),
        ];

        return new LineItemDetails($properties);
    }

    /**
     * Builds a list of {@see LineItemOption} objects from the given data.
     *
     * @param mixed[] $data
     * @return LineItemOption[]
     */
    protected function buildSelectedOptions(array $data) : array
    {
        return LineItemOptionBuilder::getNewInstance()->buildMany($data);
    }
}
