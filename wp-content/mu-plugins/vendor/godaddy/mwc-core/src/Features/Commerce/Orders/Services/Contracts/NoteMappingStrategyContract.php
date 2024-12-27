<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts;

use GoDaddy\WordPress\MWC\Common\Models\Orders\Note;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\MappingStrategyContract;

interface NoteMappingStrategyContract extends MappingStrategyContract
{
    /**
     * Saves the given remote UUID as the remote ID for the given note.
     *
     * @param Note $model
     * @param string $remoteId
     * @throws CommerceExceptionContract
     */
    public function saveRemoteId(object $model, string $remoteId) : void;

    /**
     * Gets the remote UUID associated with the given note.
     *
     * @param Note $model
     * @return string|null
     */
    public function getRemoteId(object $model) : ?string;
}
