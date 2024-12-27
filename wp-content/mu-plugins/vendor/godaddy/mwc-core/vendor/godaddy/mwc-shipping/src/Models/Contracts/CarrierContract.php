<?php

namespace GoDaddy\WordPress\MWC\Shipping\Models\Contracts;

use GoDaddy\WordPress\MWC\Common\Contracts\HasLabelContract;
use GoDaddy\WordPress\MWC\Common\Contracts\HasStringIdentifierContract;
use GoDaddy\WordPress\MWC\Common\Models\Contracts\ModelContract;

/**
 * Represents a shipping carrier, e.g., FedEx, UPS, DHL, CanadaPost.
 */
interface CarrierContract extends ModelContract, HasLabelContract, HasStringIdentifierContract
{
    /**
     * Gets carrier's package types.
     *
     * @return PackageTypeContract[]
     */
    public function getPackages() : array;

    /**
     * Sets carrier's package types.
     *
     * @param PackageTypeContract[] $value
     * @return $this
     */
    public function setPackages(array $value);

    /**
     * Gets carrier's package type by the given code.
     *
     * @param string $code
     * @return PackageTypeContract|null
     */
    public function getPackageByCode(string $code) : ?PackageTypeContract;
}
