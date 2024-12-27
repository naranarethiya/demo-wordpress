<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories;

use GoDaddy\WordPress\MWC\Common\Exceptions\WordPressDatabaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\DatabaseRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Enums\CommerceTableColumns;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Enums\CommerceTables;

/**
 * Abstract resource updates repository, for interacting with the {@see CommerceTables::ResourceUpdates} database table.
 *
 * @phpstan-type TResourceMapRow array{id: numeric-string, resource_type_id: numeric-string, commerce_id: string, remote_updated_at: string}
 */
class AbstractResourceUpdatesRepository extends AbstractResourceRepository
{
    /**
     * Adds updated at datetime information to a resource.
     *
     * @param string $remoteId
     * @param non-empty-string $updatedAt
     * @return int
     * @throws WordPressDatabaseException
     */
    public function addUpdatedAt(string $remoteId, string $updatedAt) : int
    {
        return DatabaseRepository::insert(
            CommerceTables::ResourceUpdates,
            [
                CommerceTableColumns::CommerceId      => $this->remoteIdMutationStrategy->getRemoteIdForDatabase($remoteId),
                CommerceTableColumns::ResourceTypeId  => $this->getResourceTypeId(),
                CommerceTableColumns::RemoteUpdatedAt => $updatedAt,
            ]
        );
    }

    /**
     * Gets the datetime string when a resource was last updated.
     *
     * @param string $remoteId
     * @return non-empty-string|null
     */
    public function getUpdatedAt(string $remoteId) : ?string
    {
        $tableName = CommerceTables::ResourceUpdates;

        $row = DatabaseRepository::getRow(
            implode(' ', [
                'SELECT resource_updates.'.CommerceTableColumns::RemoteUpdatedAt." FROM {$tableName} as resource_updates",
                $this->getResourceTypeJoinClause('resource_updates'),
                'WHERE '.CommerceTableColumns::CommerceId.' = %s',
            ]),
            [$this->remoteIdMutationStrategy->getRemoteIdForDatabase($remoteId)]
        );

        return TypeHelper::nonEmptyStringOrNull(ArrayHelper::get($row, 'remote_updated_at'));
    }

    /**
     * Updates a resource last updated datetime.
     *
     * @param string $remoteId
     * @param non-empty-string $updatedAt
     * @return void
     * @throws WordPressDatabaseException
     */
    public function addOrUpdateUpdatedAt(string $remoteId, string $updatedAt) : void
    {
        $existingResourceUpdatedAt = $this->getUpdatedAt($remoteId);

        if (! $existingResourceUpdatedAt) {
            $this->addUpdatedAt($remoteId, $updatedAt);
        } elseif ($updatedAt !== $existingResourceUpdatedAt) {
            $formattedRemoteId = $this->remoteIdMutationStrategy->getRemoteIdForDatabase($remoteId);

            DatabaseRepository::update(
                CommerceTables::ResourceUpdates,
                [
                    CommerceTableColumns::RemoteUpdatedAt => $updatedAt,
                ],
                [
                    CommerceTableColumns::CommerceId     => $formattedRemoteId,
                    CommerceTableColumns::ResourceTypeId => $this->getResourceTypeId(),
                ],
                ['%s'],
                ['%s', '%d'],
            );
        }
    }
}
