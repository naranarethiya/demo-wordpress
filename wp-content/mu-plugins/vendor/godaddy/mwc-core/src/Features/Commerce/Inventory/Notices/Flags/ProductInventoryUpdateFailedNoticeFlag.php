<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Notices\Flags;

use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

class ProductInventoryUpdateFailedNoticeFlag
{
    use CanGetNewInstanceTrait;

    const OPTION_NAME = '_gd_mwc_product_inventory_update_failed_notice';

    /**
     * Gets the stored failed reason.
     *
     * @return string|null
     */
    public function getFailReason() : ?string
    {
        $failReason = get_option(static::OPTION_NAME, null);

        return $failReason ? TypeHelper::string($failReason, '') : null;
    }

    /**
     * Determines whether the flag is enabled.
     *
     * @return bool
     */
    public function isOn() : bool
    {
        return ! empty($this->getFailReason());
    }

    /**
     * Determines whether the flag is disabled.
     *
     * @return bool
     */
    public function isOff() : bool
    {
        return ! $this->isOn();
    }

    /**
     * Enables the flag.
     *
     * @param string $failReason
     * @return self
     */
    public function turnOn(string $failReason) : self
    {
        update_option(static::OPTION_NAME, $failReason);

        return $this;
    }

    /**
     * Deletes the flag.
     *
     * @return self
     */
    public function turnOff() : self
    {
        delete_option(static::OPTION_NAME);

        return $this;
    }
}
