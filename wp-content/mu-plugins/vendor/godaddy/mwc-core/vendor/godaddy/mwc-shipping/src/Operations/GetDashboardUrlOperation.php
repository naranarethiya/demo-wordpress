<?php

namespace GoDaddy\WordPress\MWC\Shipping\Operations;

use GoDaddy\WordPress\MWC\Shipping\Contracts\GetDashboardUrlOperationContract;
use GoDaddy\WordPress\MWC\Shipping\Traits\HasAccountTrait;

class GetDashboardUrlOperation implements GetDashboardUrlOperationContract
{
    use HasAccountTrait;

    /** @var string */
    protected $returnUrl;

    /** @var string */
    protected $dashboardUrl;

    /**
     * {@inheritDoc}
     */
    public function getReturnUrl() : string
    {
        return $this->returnUrl;
    }

    /**
     * {@inheritDoc}
     */
    public function setReturnUrl(string $value)
    {
        $this->returnUrl = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getDashboardUrl() : string
    {
        return $this->dashboardUrl;
    }

    /**
     * {@inheritDoc}
     */
    public function setDashboardUrl(string $value)
    {
        $this->dashboardUrl = $value;

        return $this;
    }
}
