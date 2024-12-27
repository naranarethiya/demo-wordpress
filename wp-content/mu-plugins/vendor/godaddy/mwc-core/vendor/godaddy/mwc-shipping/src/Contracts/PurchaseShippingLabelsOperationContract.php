<?php

namespace GoDaddy\WordPress\MWC\Shipping\Contracts;

interface PurchaseShippingLabelsOperationContract extends OperationContract, HasAccountContract, HasShipmentContract
{
    /**
     * Gets shipping label layout.
     *
     * @return string
     */
    public function getLayout() : string;

    /**
     * Sets shipping label layout.
     *
     * @param string $value
     * @return $this
     */
    public function setLayout(string $value);

    /**
     * Gets shipping label format.
     *
     * @return string
     */
    public function getFormat() : string;

    /**
     * Sets shipping label format.
     *
     * @param string $value
     * @return $this
     */
    public function setFormat(string $value);
}
