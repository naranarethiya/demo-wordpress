<?php

namespace GoDaddy\WordPress\MWC\Shipping\Traits;

use GoDaddy\WordPress\MWC\Shipping\Contracts\GatewayRequestAdapterContract;
use GoDaddy\WordPress\MWC\Shipping\Contracts\PurchaseShippingLabelsOperationContract;
use GoDaddy\WordPress\MWC\Shipping\Exceptions\Contracts\ShippingExceptionContract;
use GoDaddy\WordPress\MWC\Shipping\Models\ShippingLabel;

/**
 * Provides methods to an object to purchase shipping labels.
 *
 * Can be used to fulfill {@see CanPurchaseShippingLabelsContract} on a subclass of {@see AbstractGateway}.
 *
 * @see ShippingLabel
 */
trait CanPurchaseShippingLabelsTrait
{
    use AdaptsRequestsTrait;

    /** @var class-string<GatewayRequestAdapterContract> */
    protected $purchaseShippingLabelsRequestAdapter;

    /**
     * Purchase shipping labels for shipments.
     *
     * @param PurchaseShippingLabelsOperationContract $operation
     * @return PurchaseShippingLabelsOperationContract
     * @throws ShippingExceptionContract
     */
    public function purchase(PurchaseShippingLabelsOperationContract $operation) : PurchaseShippingLabelsOperationContract
    {
        return $this->doAdaptedRequest(new $this->purchaseShippingLabelsRequestAdapter($operation));
    }
}
