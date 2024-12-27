<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Shipping\Providers\ShipEngine\Adapters;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\RequestContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Shipping\Providers\ShipEngine\Http\Request;
use GoDaddy\WordPress\MWC\Shipping\Adapters\AbstractGatewayRequestAdapter;
use GoDaddy\WordPress\MWC\Shipping\Contracts\GetDashboardUrlOperationContract;
use GoDaddy\WordPress\MWC\Shipping\Exceptions\ShippingException;

class GetDashboardUrlRequestAdapter extends AbstractGatewayRequestAdapter
{
    /** @var GetDashboardUrlOperationContract */
    protected $operation;

    public function __construct(GetDashboardUrlOperationContract $operation)
    {
        $this->operation = $operation;
    }

    /**
     * {@inheritDoc}
     */
    public function convertFromSource() : RequestContract
    {
        return Request::withAuth()
            ->setPath('/shipping/dashboard/redirect')
            ->setMethod('post')
            ->setBody([
                'externalAccountId' => $this->operation->getAccount()->getId(),
                'returnUrl'         => $this->operation->getReturnUrl(),
            ]);
    }

    /**
     * {@inheritDoc}
     */
    protected function convertResponse(ResponseContract $response)
    {
        if (! $dashboardUrl = ArrayHelper::get(ArrayHelper::wrap($response->getBody()), 'redirectUrl')) {
            throw new ShippingException('The response does not include a redirect URL.');
        }

        $this->operation->setDashboardUrl($dashboardUrl);

        return $this->operation;
    }
}
