<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories;

use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Contracts\CanGetResourceTypeContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Enums\CommerceTableColumns;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Enums\CommerceTables;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\Traits\HasCommerceResourceTypeTrait;
use GoDaddy\WordPress\MWC\Core\Repositories\Strategies\Contracts\RemoteIdStrategyContract;
use GoDaddy\WordPress\MWC\Core\Repositories\Strategies\PassThruRemoteIdMutationStrategy;

/**
 * Abstract resource repository.
 */
abstract class AbstractResourceRepository implements CanGetResourceTypeContract
{
    use HasCommerceResourceTypeTrait;

    /** @var CommerceContextContract */
    protected CommerceContextContract $commerceContext;

    /** @var RemoteIdStrategyContract */
    protected RemoteIdStrategyContract $remoteIdMutationStrategy;

    /**
     * Constructor.
     *
     * The {@see RemoteIdStrategyContract} argument points to the method used to transform the ID when saving and returning the result.
     * When null, this will use a pass-through strategy via {@see PassThruRemoteIdMutationStrategy}, which does not mutate the ID.
     *
     * @param CommerceContextContract $commerceContext
     * @param RemoteIdStrategyContract|null $remoteIdMutationStrategy
     */
    public function __construct(CommerceContextContract $commerceContext, ?RemoteIdStrategyContract $remoteIdMutationStrategy = null)
    {
        $this->commerceContext = $commerceContext;
        $this->remoteIdMutationStrategy = $remoteIdMutationStrategy ?? PassThruRemoteIdMutationStrategy::getNewInstance();
    }

    /**
     * Gets a SQL clause that can be used to perform an inner join on the resource type tables.
     *
     * @param string $idMapTableNameAlias default value is `map_ids`
     * @param string $resourceMapTableNameAlias default value is `resource_types`
     * @return string
     */
    protected function getResourceTypeJoinClause(string $idMapTableNameAlias = 'map_ids', string $resourceMapTableNameAlias = 'resource_types') : string
    {
        $resourceMapTableName = CommerceTables::ResourceTypes;
        $resourceType = TypeHelper::string(esc_sql($this->getResourceType() ?: ''), '');

        return implode(' ', [
            "INNER JOIN {$resourceMapTableName} AS {$resourceMapTableNameAlias}",
            "ON {$resourceMapTableNameAlias}.".CommerceTableColumns::Id." = {$idMapTableNameAlias}.".CommerceTableColumns::ResourceTypeId,
            "AND {$resourceMapTableNameAlias}.".CommerceTableColumns::Name." = '{$resourceType}'",
        ]);
    }
}
