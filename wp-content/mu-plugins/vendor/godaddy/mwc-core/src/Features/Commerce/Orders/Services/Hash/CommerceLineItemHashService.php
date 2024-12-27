<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Hash;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Providers\DataObjects\LineItem;

/**
 * @extends AbstractHashService<LineItem>
 */
class CommerceLineItemHashService extends AbstractHashService
{
    /**
     * {@inheritDoc}
     * @param LineItem $model
     * @return string[]
     */
    protected function getValuesForHash(object $model) : array
    {
        return [
            'LineItem',
            $model->name,
            (string) ($model->details->sku ?? null),
            (string) $model->quantity,
        ];
    }
}
