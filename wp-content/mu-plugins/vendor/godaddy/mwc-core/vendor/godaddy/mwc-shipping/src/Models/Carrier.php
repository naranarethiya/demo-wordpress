<?php

namespace GoDaddy\WordPress\MWC\Shipping\Models;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;
use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasStringIdentifierTrait;
use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\CarrierContract;
use GoDaddy\WordPress\MWC\Shipping\Models\Contracts\PackageTypeContract;

class Carrier extends AbstractModel implements CarrierContract
{
    use HasStringIdentifierTrait;
    use HasLabelTrait;

    /** @var PackageTypeContract[] */
    protected array $packages = [];

    /** @var array<string, PackageTypeContract> */
    protected array $packagesByCode = [];

    /**
     * {@inheritDoc}
     */
    public function getPackages() : array
    {
        return $this->packages;
    }

    /**
     * {@inheritDoc}
     */
    public function setPackages(array $value)
    {
        $this->packages = $value;

        $this->indexPackagesByCode();

        return $this;
    }

    /**
     * Indexes package types by code.
     *
     * @return void
     */
    protected function indexPackagesByCode() : void
    {
        $this->packagesByCode = ArrayHelper::indexBy($this->packages, static fn (PackageTypeContract $package) => $package->getCode());
    }

    /**
     * {@inheritDoc}
     */
    public function getPackageByCode(string $code) : ?PackageTypeContract
    {
        return $this->packagesByCode[$code] ?? null;
    }
}
