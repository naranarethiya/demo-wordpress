<?php

namespace GoDaddy\WordPress\MWC\Shipping\Traits;

namespace GoDaddy\WordPress\MWC\Shipping\Traits;

use GoDaddy\WordPress\MWC\Shipping\Contracts\PackageContract;

trait HasPackageTrait
{
    /** @var PackageContract */
    protected $package;

    /**
     * @return PackageContract
     */
    public function getPackage() : PackageContract
    {
        return $this->package;
    }

    /**
     * @param PackageContract $package
     *
     * @return $this
     */
    public function setPackage(PackageContract $package)
    {
        $this->package = $package;

        return $this;
    }
}
