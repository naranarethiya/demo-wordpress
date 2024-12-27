<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\Traits;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\DatabaseRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Enums\CommerceTableColumns;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Enums\CommerceTables;

/**
 * Has Commerce resource type trait.
 */
trait HasCommerceResourceTypeTrait
{
    /** @var string type of resources managed by this repository */
    protected string $resourceType = '';

    /**
     * Get the resource type handled by this class.
     *
     * @return string|null
     */
    public function getResourceType() : ?string
    {
        return $this->resourceType;
    }

    /**
     * Gets the resource type ID.
     *
     * @return int|null
     */
    public function getResourceTypeId() : ?int
    {
        $tableName = CommerceTables::ResourceTypes;

        $row = DatabaseRepository::getRow(
            'SELECT '.CommerceTableColumns::Id." FROM {$tableName} WHERE ".CommerceTableColumns::Name.' = %s',
            [$this->getResourceType()]
        );

        return TypeHelper::int(ArrayHelper::get($row, 'id'), 0) ?: null;
    }
}
