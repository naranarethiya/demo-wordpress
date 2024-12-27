<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Contracts;

/**
 * Has remote (Commerce) resource identifiers.
 */
interface HasRemoteIdsContract
{
    /**
     * Sets the remote resource IDs.
     *
     * @param string[]|null $value
     * @return $this
     */
    public function setIds(?array $value);

    /**
     * Gets the remote resource IDs.
     *
     * @return string[]|null
     */
    public function getIds() : ?array;
}
