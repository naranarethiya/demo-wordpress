<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts;

interface CanGetRemoteIdContract
{
    /**
     * Gets the remote UUID associated with the given model.
     *
     * @param object $model
     * @return string|null
     */
    public function getRemoteId(object $model) : ?string;
}
