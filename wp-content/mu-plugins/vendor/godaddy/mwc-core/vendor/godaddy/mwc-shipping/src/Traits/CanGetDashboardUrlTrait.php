<?php

namespace GoDaddy\WordPress\MWC\Shipping\Traits;

use GoDaddy\WordPress\MWC\Shipping\Contracts\CanGetDashboardUrlContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\GatewayRequestAdapterContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\GetDashboardUrlOperationContract;

/**
 * Can be used to fulfill {@see CanGetDashboardUrlContract} on a subclass of {@see AbstractGateway}.
 */
trait CanGetDashboardUrlTrait
{
    use AdaptsRequestsTrait;

    /** @var class-string<GatewayRequestAdapterContract> */
    protected $getDashboardUrlRequestAdapter;

    /**
     * {@inheritdoc}
     */
    public function getDashboardUrl(GetDashboardUrlOperationContract $operation) : GetDashboardUrlOperationContract
    {
        return $this->doAdaptedRequest(new $this->getDashboardUrlRequestAdapter($operation));
    }
}
