<?php

namespace GoDaddy\WordPress\MWC\Shipping\Contracts;

interface HasPackageContract
{
    /**
     * @return PackageContract
     */
    public function getPackage() : PackageContract;

    /**
     * @return $this
     */
    public function setPackage(PackageContract $package);
}
