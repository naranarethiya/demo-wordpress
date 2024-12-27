<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\DataSources\WooCommerce\Builders\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractResourceAssociation;

/**
 * Builds associations between remote resources and their local IDs.
 */
interface ResourceAssociationBuilderContract
{
    /**
     * Builds associations between an array of remote resources and their local IDs.
     * Any remote resources that do not have local IDs will have local entities created.
     *
     * @param AbstractDataObject[] $resources
     * @return AbstractResourceAssociation[]
     */
    public function build(array $resources) : array;
}
