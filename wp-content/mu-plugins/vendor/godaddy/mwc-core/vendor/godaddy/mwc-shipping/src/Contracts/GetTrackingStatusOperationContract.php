<?php

namespace GoDaddy\WordPress\MWC\Shipping\Contracts;

interface GetTrackingStatusOperationContract extends OperationContract, HasAccountContract, HasPackageContract
{
    /**
     * Sets shipment tracking number.
     *
     * @param string $trackingNumber
     * @return $this
     */
    public function setTrackingNumber(string $trackingNumber);

    /**
     * Gets shipment tracking number.
     *
     * @return string
     */
    public function getTrackingNumber() : string;

    /**
     * Sets shipment tracking URL.
     *
     * @param string $trackingUrl
     * @return $this
     */
    public function setTrackingUrl(string $trackingUrl);

    /**
     * Gets shipment tracking URL.
     *
     * @return string
     */
    public function getTrackingUrl() : string;
}
