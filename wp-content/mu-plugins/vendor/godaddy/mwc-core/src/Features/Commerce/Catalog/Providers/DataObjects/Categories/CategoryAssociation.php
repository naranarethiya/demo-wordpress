<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Categories;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractResourceAssociation;

class CategoryAssociation extends AbstractResourceAssociation
{
    /** @var Category remote category from the platform */
    public AbstractDataObject $remoteResource;

    /**
     * Constructor.
     *
     * @param array{
     *     remoteResource: Category,
     *     localId: int,
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
