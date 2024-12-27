<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Adapters;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\GoDaddy\Http\Requests\Request;

class CreateLocationRequestAdapter extends AbstractUpsertLocationRequestAdapter
{
    /**
     * {@inheritDoc}
     */
    public function convertFromSource() : Request
    {
        $location = $this->input->location;

        $externalIds = array_map(function ($externalId) {
            return $externalId->toArray();
        }, $location->externalIds);

        $data = [
            'type'        => $location->type,
            'externalIds' => $externalIds,
            'active'      => $location->active,
            'priority'    => $location->priority,
        ];

        if (isset($location->address)) {
            ArrayHelper::set($data, 'address', array_filter($location->address->toArray()));
        }

        return $this->getBaseRequest()
            ->setPath('/inventory-locations')
            ->setMethod('POST')
            ->setBody($data);
    }
}
