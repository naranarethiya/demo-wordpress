<?php

namespace GoDaddy\WordPress\MWC\Common\Platforms\Repositories;

use GoDaddy\WordPress\MWC\Common\Platforms\Contracts\PlatformRepositoryContract;

/**
 * Abstract platform repository class, containing shared logic between platforms.
 */
abstract class AbstractPlatformRepository implements PlatformRepositoryContract
{
    /**
     * {@inheritDoc}
     */
    public function isReseller() : bool
    {
        return ! $this->hasPlatformData() || (int) $this->getResellerId() !== 1;
    }

    /**
     * {@inheritDoc}
     */
    public function isTlaSite() : bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getChannelId() : string
    {
        return '';
    }

    /** {@inheritDoc} */
    public function getBlockedPlugins() : array
    {
        return [];
    }
}
