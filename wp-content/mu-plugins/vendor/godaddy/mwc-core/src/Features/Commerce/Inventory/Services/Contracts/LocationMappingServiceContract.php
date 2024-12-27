<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts;

interface LocationMappingServiceContract
{
    /**
     * Saves the given remote ID.
     *
     * @param string $remoteId
     */
    public function saveRemoteId(string $remoteId) : void;

    /**
     * Gets the remote ID.
     *
     * @return string|null
     */
    public function getRemoteId() : ?string;
}
