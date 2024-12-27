<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanConvertToArrayTrait;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\ListCategoriesOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Traits\HasAltIdFilterTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Traits\HasPageSizeTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Traits\HasPageTokenTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Traits\HasParentIdTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Traits\HasRemoteIdsTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Traits\HasSortingTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\HasLocalIdsTrait;

/**
 * Operation for listing categories.
 */
class ListCategoriesOperation implements ListCategoriesOperationContract
{
    use CanConvertToArrayTrait {
        CanConvertToArrayTrait::toArray as traitToArray;
    }
    use CanGetNewInstanceTrait;
    use HasPageSizeTrait;
    use HasPageTokenTrait;
    use HasSortingTrait;
    use HasAltIdFilterTrait;
    use HasParentIdTrait;
    use HasLocalIdsTrait;
    use HasRemoteIdsTrait;

    /**
     * Overrides the {@see CanConvertToArrayTrait::toArray()} method to exclude some irrelevant properties and only return not-null values.
     *
     * @return array<string, mixed>
     */
    public function toArray() : array
    {
        $data = $this->traitToArray();

        return ArrayHelper::whereNotNull(ArrayHelper::except($data, ['localIds']));
    }
}
