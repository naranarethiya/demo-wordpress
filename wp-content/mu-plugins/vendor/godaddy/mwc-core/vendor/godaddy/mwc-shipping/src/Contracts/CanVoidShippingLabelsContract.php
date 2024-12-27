<?php

namespace GoDaddy\WordPress\MWC\Shipping\Contracts;

interface CanVoidShippingLabelsContract
{
    /**
     * Voids shipping labels.
     *
     * @param VoidShippingLabelOperationContract $operation
     * @return VoidShippingLabelOperationContract
     */
    public function void(VoidShippingLabelOperationContract $operation) : VoidShippingLabelOperationContract;
}
