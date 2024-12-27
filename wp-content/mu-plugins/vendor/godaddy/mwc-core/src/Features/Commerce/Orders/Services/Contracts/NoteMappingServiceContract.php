<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Orders\Services\Contracts;

use GoDaddy\WordPress\MWC\Common\Models\Orders\Note;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\MappingServiceContract;

interface NoteMappingServiceContract extends MappingServiceContract
{
    /**
     * {@inheritDoc}
     *
     * @param Note  $model
     * @param string $remoteId
     *
     * @return void
     */
    public function saveRemoteId(object $model, string $remoteId) : void;

    /**
     * {@inheritDoc}
     *
     * @param Note $model
     *
     * @return string|null
     */
    public function getRemoteId(object $model) : ?string;
}
