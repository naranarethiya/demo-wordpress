<?php

namespace GoDaddy\WordPress\MWC\Shipping\Contracts;

interface GetDashboardUrlOperationContract extends OperationContract, HasAccountContract
{
    /**
     * Gets the URL that merchants should return to after they logout of the dashboard.
     *
     * @return string
     */
    public function getReturnUrl() : string;

    /**
     * Sets the URL that merchants should return to after they logout of the dashboard.
     *
     * @param string $value
     * @return $this
     */
    public function setReturnUrl(string $value);

    /**
     * Gets the URL for the dashboard.
     *
     * @return string
     */
    public function getDashboardUrl() : string;

    /**
     * Sets the URL for the dashboard.
     *
     * @param string $value
     * @return $this
     */
    public function setDashboardUrl(string $value);
}
