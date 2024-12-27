<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Adapters;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Http\Requests\Request;

class UpdateLocationRequestAdapter extends AbstractUpsertLocationRequestAdapter
{
    /**
     * {@inheritDoc}
     */
    public function convertFromSource() : Request
    {
        $location = $this->input->location;

        $data = [];

        if (isset($location->type)) {
            ArrayHelper::set($data, 'type', $location->type);
        }

        if (isset($location->address)) {
            ArrayHelper::set($data, 'address', $location->address->toArray());
        }

        if (isset($location->active)) {
            ArrayHelper::set($data, 'active', $location->active);
        }

        if (isset($location->priority)) {
            ArrayHelper::set($data, 'priority', $location->priority);
        }

        return $this->getBaseRequest()
            ->setPath('/inventory-locations/'.$location->inventoryLocationId)
            ->setMethod('PATCH')
            ->setBody($data);
    }
}
