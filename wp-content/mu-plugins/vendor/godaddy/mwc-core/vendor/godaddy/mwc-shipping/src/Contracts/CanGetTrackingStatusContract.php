<?php

namespace GoDaddy\WordPress\MWC\Shipping\Contracts;

interface CanGetTrackingStatusContract
{
    /**
     * Retrieves shipment tracking status.
     *
     * @param GetTrackingStatusOperationContract $operation
     * @return GetTrackingStatusOperationContract
     */
    public function status(GetTrackingStatusOperationContract $operation) : GetTrackingStatusOperationContract;
}
