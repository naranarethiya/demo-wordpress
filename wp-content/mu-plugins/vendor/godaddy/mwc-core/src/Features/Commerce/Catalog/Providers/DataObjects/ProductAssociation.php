<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractResourceAssociation;

/**
 * Maintains an association between a local WooCommerce product ID and a remote {@see ProductBase} object.
 *
 * @method static static getNewInstance(array $data)
 */
class ProductAssociation extends AbstractResourceAssociation
{
    /** @var ProductBase remote product from the platform */
    public AbstractDataObject $remoteResource;

    /**
     * Constructor.
     *
     * @param array{
     *     remoteResource: ProductBase,
     *     localId: int,
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
