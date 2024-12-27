<?php

namespace GoDaddy\WordPress\MWC\Common\Cache\Traits;

use GoDaddy\WordPress\MWC\Common\Cache\Cache;

/**
 * Disables cache persistence.
 *
 * A {@see Cache} class using this trait will only use memory cache.
 *
 * @require-extends Cache
 */
trait IsMemoryCacheOnlyTrait
{
    /**
     * {@inheritDoc}
     */
    public function getPersisted()
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    protected function setPersisted($value)
    {
    }

    /**
     * {@inheritDoc}
     */
    protected function clearPersisted()
    {
    }
}
