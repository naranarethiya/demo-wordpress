<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Shipping\Providers\ShipEngine\Adapters;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\RequestContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Shipping\Providers\ShipEngine\Http\Request;
use GoDaddy\WordPress\MWC\Shipping\Adapters\AbstractGatewayRequestAdapter;
use GoDaddy\WordPress\MWC\Shipping\Contracts\GetTrackingStatusOperationContract;
use GoDaddy\WordPress\MWC\Shipping\Exceptions\ShippingException;

class TrackingStatusRequestAdapter extends AbstractGatewayRequestAdapter
{
    /** @var GetTrackingStatusOperationContract */
    protected $operation;

    public function __construct(GetTrackingStatusOperationContract $operation)
    {
        $this->operation = $operation;
    }

    /**
     * {@inheritDoc}
     * @throws ShippingException
     */
    public function convertFromSource() : RequestContract
    {
        $shippingLabel = $this->operation->getPackage()->getShippingLabel();
        $labelRemoteId = is_null($shippingLabel) ? null : $shippingLabel->getRemoteId();

        if (is_null($labelRemoteId)) {
            throw new ShippingException('No linked shipping label found.');
        }

        return Request::withAuth()
            ->setPath("/shipping/proxy/shipengine/v1/labels/{$labelRemoteId}/track")
            ->setMethod('get')
            ->setQuery([
                'externalAccountId' => $this->operation->getAccount()->getId(),
            ]);
    }

    /** {@inheritDoc} */
    protected function convertResponse(ResponseContract $response)
    {
        $responseBody = ArrayHelper::wrap($response->getBody());

        $trackingNumber = ArrayHelper::getStringValueForKey($responseBody, 'tracking_number');
        $trackingUrl = ArrayHelper::getStringValueForKey($responseBody, 'tracking_url');

        if (empty($trackingNumber) && empty($trackingUrl)) {
            throw new ShippingException('Tracking information is missing.');
        }

        $this->operation->setTrackingNumber($trackingNumber);
        $this->operation->setTrackingUrl($trackingUrl);
        $this->operation->getPackage()->setTrackingNumber($trackingNumber)->setTrackingUrl($trackingUrl);

        return $this->operation;
    }
}
