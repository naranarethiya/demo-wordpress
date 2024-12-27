<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts;

use GoDaddy\WordPress\MWC\Common\Models\Term;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Contracts\MappingServiceContract;

/**
 * Categories mapping service contract.
 *
 * Interface for a service that maps local product categories to Commerce categories.
 */
interface CategoriesMappingServiceContract extends MappingServiceContract
{
    /**
     * Saves the given remote UUID as the remote ID for the given model.
     *
     * @param Term|object $model
     * @param string $remoteId
     * @throws CommerceExceptionContract
     */
    public function saveRemoteId(object $model, string $remoteId) : void;

    /**
     * Gets the remote UUID associated with the given model.
     *
     * @param Term|object $model
     * @return string|null
     */
    public function getRemoteId(object $model) : ?string;
}
