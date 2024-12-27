<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Shipping\Providers\ShipEngine\Adapters;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\RequestContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Shipping\Exceptions\ShippingLabelVoidNotApprovedException;
use GoDaddy\WordPress\MWC\Core\Features\Shipping\Providers\ShipEngine\Http\Request;
use GoDaddy\WordPress\MWC\Shipping\Adapters\AbstractGatewayRequestAdapter;
use GoDaddy\WordPress\MWC\Shipping\Contracts\VoidShippingLabelOperationContract;
use GoDaddy\WordPress\MWC\Shipping\Exceptions\ShippingException;
use GoDaddy\WordPress\MWC\Shipping\Models\Labels\Statuses\VoidedLabelStatus;
use GoDaddy\WordPress\MWC\Shipping\Models\Packages\Statuses\CancelledPackageStatus;

class VoidShippingLabelRequestAdapter extends AbstractGatewayRequestAdapter
{
    /**
     * @var VoidShippingLabelOperationContract
     */
    protected $operation;

    /**
     * @param VoidShippingLabelOperationContract $operation
     */
    public function __construct(VoidShippingLabelOperationContract $operation)
    {
        $this->operation = $operation;
    }

    /**
     * {@inheritDoc}
     * @throws ShippingLabelVoidNotApprovedException
     * @throws ShippingException
     */
    protected function convertResponse(ResponseContract $response) : VoidShippingLabelOperationContract
    {
        $body = $response->getBody();

        $approved = ArrayHelper::get(ArrayHelper::wrap($body), 'approved');

        if (is_null($approved)) {
            throw new ShippingException('Label response did not include an approval state.');
        }

        if (! is_bool($approved)) {
            throw new ShippingException('Label response approval state is malformed.');
        }

        if (false === $approved) {
            throw new ShippingLabelVoidNotApprovedException('The request to refund this label has been denied.');
        }

        $label = $this->operation->getPackage()->setStatus(new CancelledPackageStatus)->getShippingLabel();

        if (! is_null($label)) {
            $label->setStatus(new VoidedLabelStatus);
        }

        return $this->operation;
    }

    /**
     * {@inheritDoc}
     * @throws ShippingException
     * @return Request
     */
    public function convertFromSource() : RequestContract
    {
        $label = $this->operation->getPackage()->getShippingLabel();

        if (is_null($label)) {
            throw new ShippingException('Package does not have a label');
        }

        return Request::withAuth()
                      ->setPath("/shipping/proxy/shipengine/v1/labels/{$label->getRemoteId()}/void")
                      ->setMethod('put')
                      ->setBody([
                          'externalAccountId' => $this->operation->getAccount()->getId(),
                      ]);
    }
}
