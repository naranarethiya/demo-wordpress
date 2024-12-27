<?php

namespace GoDaddy\WordPress\MWC\Shipping\Models\Packages\Statuses;

use GoDaddy\WordPress\MWC\Common\Traits\CanConvertToArrayTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;
use GoDaddy\WordPress\MWC\Shipping\Contracts\PackageStatusContract;

/**
 * Status for newly created packages.
 */
class CreatedPackageStatus implements PackageStatusContract
{
    use HasLabelTrait;
    use CanConvertToArrayTrait;

    /**
     * Created package status constructor.
     *
     * Initializes the status by setting its name and label.
     */
    public function __construct()
    {
        $this->setName('created');
        $this->setLabel(__('Created', 'mwc-shipping'));
    }

    /**
     * Determines whether the status can fulfill items in the package.
     *
     * A newly created package cannot fulfill items.
     * The status of the package is expected to change to label-created once a label is purchased.
     *
     * @return bool
     */
    public function canFulfillItems() : bool
    {
        return false;
    }
}
