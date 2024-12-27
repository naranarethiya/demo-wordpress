<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\Assets;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\AbstractAsset;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractDataObject;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\AbstractResourceAssociation;

class AssetAssociation extends AbstractResourceAssociation
{
    /** @var AbstractAsset */
    public AbstractDataObject $remoteResource;

    /**
     * @param array{
     *     remoteResource: AbstractAsset,
     *     localId: int,
     * } $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
