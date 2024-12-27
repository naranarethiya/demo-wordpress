<?php

namespace GoDaddy\WordPress\MWC\Shipping\Contracts;

use GoDaddy\WordPress\MWC\Shipping\Exceptions\Contracts\ShippingExceptionContract;

interface CanGetDashboardUrlContract
{
    /**
     * Gets the dashboard URL for a shipping account.
     *
     * @param GetDashboardUrlOperationContract $operation
     * @return GetDashboardUrlOperationContract
     * @throws ShippingExceptionContract
     */
    public function getDashboardUrl(GetDashboardUrlOperationContract $operation) : GetDashboardUrlOperationContract;
}
