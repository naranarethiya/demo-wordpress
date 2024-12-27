<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts;

use GoDaddy\WordPress\MWC\Common\Models\Term;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\MappingStrategyContract;

/**
 * Categories mapping strategy.
 */
interface CategoriesMappingStrategyContract extends MappingStrategyContract
{
    /**
     * Saves the given remote UUID as the remote ID for the given model.
     *
     * @param Term $model
     * @param string $remoteId
     * @throws CommerceExceptionContract
     */
    public function saveRemoteId(object $model, string $remoteId) : void;

    /**
     * Gets the remote UUID associated with the given model.
     *
     * @param Term $model
     * @return string|null
     */
    public function getRemoteId(object $model) : ?string;
}
