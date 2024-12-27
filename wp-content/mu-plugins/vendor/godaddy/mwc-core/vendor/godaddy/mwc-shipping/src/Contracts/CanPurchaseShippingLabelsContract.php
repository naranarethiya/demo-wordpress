<?php

namespace GoDaddy\WordPress\MWC\Shipping\Contracts;

interface CanPurchaseShippingLabelsContract
{
    /**
     * Purchases shipping labels.
     *
     * @param PurchaseShippingLabelsOperationContract $operation
     * @return PurchaseShippingLabelsOperationContract
     */
    public function purchase(PurchaseShippingLabelsOperationContract $operation) : PurchaseShippingLabelsOperationContract;
}
