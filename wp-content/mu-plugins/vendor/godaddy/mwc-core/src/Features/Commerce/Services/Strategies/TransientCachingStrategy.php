<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Strategies;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Strategies\Contracts\PersistentCachingStrategyContract;

/**
 * Caching strategy that utilizes transients.
 */
class TransientCachingStrategy implements PersistentCachingStrategyContract
{
    /**
     * Gets a unique transient name for the supplied group and item identifier.
     *
     * Transients don't have cache group so we have to concatenate the group name and item identifier.
     *
     * @param string $group group name (e.g. "godaddy-commerce-products")
     * @param string $itemIdentifier item identifier (e.g. the product UUID)
     * @return string
     */
    protected function getTransientName(string $group, string $itemIdentifier) : string
    {
        return "{$group}-{$itemIdentifier}";
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $key, string $group)
    {
        $result = get_transient($this->getTransientName($group, $key));

        // false would signal that the resource was not found, but in this case we want to return null instead
        return false === $result ? null : $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getMany(array $keys, string $group) : array
    {
        $items = [];

        foreach ($keys as $key) {
            $items[$key] = $this->get($key, $group);
        }

        return $items;
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, string $group, string $jsonResource, int $ttl) : void
    {
        set_transient($this->getTransientName($group, $key), $jsonResource, $ttl);
    }

    /**
     * {@inheritDoc}
     */
    public function setMany(string $group, array $jsonResources, int $ttl) : void
    {
        foreach ($jsonResources as $key => $jsonResource) {
            $this->set($key, $group, $jsonResource, $ttl);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function remove(string $key, string $group) : void
    {
        delete_transient($this->getTransientName($group, $key));
    }
}
