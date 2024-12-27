<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Models;

use GoDaddy\WordPress\MWC\Common\Contracts\CanConvertToArrayContract;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanConvertToArrayTrait;
use GoDaddy\WordPress\MWC\Core\Repositories\AbstractResourceMapRepository;

/**
 * A data object that represents a map of resource local ID to remote ID, sourced from a row in {@see AbstractResourceMapRepository::MAP_IDS_TABLE}.
 *
 * @see AbstractResourceMapRepository
 *
 * @phpstan-import-type TResourceMapRow from AbstractResourceMapRepository
 */
class ResourceMap implements CanConvertToArrayContract
{
    use CanConvertToArrayTrait;

    /** @var string The (commerce) remote ID. */
    public string $commerceId;

    /** @var int Primary ID of the map's row in the database. */
    public int $id;

    /** @var int The local ID as stored in the database. */
    public int $localId;

    final public function __construct(int $id, string $commerceId, int $localId)
    {
        $this->id = $id;
        $this->commerceId = $commerceId;
        $this->localId = $localId;
    }

    /**
     * @param TResourceMapRow $row
     */
    public static function fromRow(array $row) : ResourceMap
    {
        return new static(
            TypeHelper::int($row['id'], 0),
            $row['commerce_id'],
            TypeHelper::int($row['local_id'], 0)
        );
    }
}
