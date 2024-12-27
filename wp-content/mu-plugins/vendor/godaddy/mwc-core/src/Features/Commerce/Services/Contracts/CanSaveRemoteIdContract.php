<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;

interface CanSaveRemoteIdContract
{
    /**
     * Saves the given remote UUID as the remote ID for the given model.
     *
     * @param object $model
     * @param string $remoteId
     * @throws CommerceExceptionContract
     */
    public function saveRemoteId(object $model, string $remoteId) : void;
}
