<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\Adapters;

use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\Enums\LineItemType;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataSources\Contracts\DataObjectAdapterContract;

class LineItemTypeAdapter implements DataObjectAdapterContract
{
    /**
     * Converts a Commerce's line item type.
     *
     * @param LineItemType::* $source
     */
    public function convertFromSource($source) : void
    {
        // No-op
    }

    /**
     * Converts a line item into a Commerce's line item type.
     *
     * @param LineItem $target
     * @return LineItemType::*
     */
    public function convertToSource($target) : string
    {
        if ($target->getIsDownloadable()) {
            return LineItemType::Digital;
        }

        if ($target->getIsVirtual()) {
            return LineItemType::Service;
        }

        return LineItemType::Physical;
    }
}
