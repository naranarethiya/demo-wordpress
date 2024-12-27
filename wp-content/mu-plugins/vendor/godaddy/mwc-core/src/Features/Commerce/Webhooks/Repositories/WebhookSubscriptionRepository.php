<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Repositories;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Exceptions\WordPressDatabaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\DatabaseRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Enums\CommerceTableColumns;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Enums\CommerceTables;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\Context;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Repositories\CommerceContextRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Actions\CreateCommerceWebhookSubscriptionsTableAction;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Cache\WebhookSubscriptionCache;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\DataObjects\Subscription;
use InvalidArgumentException;

class WebhookSubscriptionRepository
{
    /**
     * Creates a new subscription entry for the given object.
     *
     * @param Subscription $subscription
     * @return int
     * @throws WordPressDatabaseException|InvalidArgumentException
     */
    public function createSubscription(Subscription $subscription) : int
    {
        if (! $subscription->secret) {
            throw new InvalidArgumentException('You cannot save a subscription without its secret.');
        }

        $contextId = CommerceContextRepository::getInstance()->findOrCreateContextWithCache($subscription->context->storeId);

        /*
         *  It's possible for the cache to be in a state where it contains an empty subscription (due to previous failures).
         *  This will ensure that the cache is cleared and the next request will fetch the correct data.
         */
        $this->clearCache($contextId);

        return DatabaseRepository::insert(
            CreateCommerceWebhookSubscriptionsTableAction::getTableName(),
            [
                CommerceTableColumns::CommerceContextId => $contextId,
                CommerceTableColumns::SubscriptionId    => $subscription->id,
                CommerceTableColumns::Name              => $subscription->name,
                CommerceTableColumns::Description       => $subscription->description,
                CommerceTableColumns::EventTypes        => json_encode($subscription->eventTypes),
                CommerceTableColumns::DeliveryUrl       => $subscription->deliveryUrl,
                CommerceTableColumns::IsEnabled         => $subscription->isEnabled ? 1 : 0,
                CommerceTableColumns::Secret            => $subscription->secret,
                CommerceTableColumns::CreatedAt         => $subscription->createdAt,
                CommerceTableColumns::UpdatedAt         => $subscription->updatedAt,
            ]
        );
    }

    /**
     * Gets a subscription row by context ID.
     *
     * @param int $contextId
     * @return Subscription|null
     */
    public function getSubscriptionByContextId(int $contextId) : ?Subscription
    {
        return $this->getSubscriptionByColumnAndId(CommerceTableColumns::CommerceContextId, '%d', $contextId);
    }

    /**
     * Gets a subscription by its remote UUID.
     *
     * @param string $subscriptionId
     * @return Subscription|null
     */
    public function getSubscriptionById(string $subscriptionId) : ?Subscription
    {
        return $this->getSubscriptionByColumnAndId(CommerceTableColumns::SubscriptionId, '%s', $subscriptionId);
    }

    /**
     * Gets a subscription by a column and ID.
     *
     * @param string $column
     * @param string $columnFormat
     * @param string|int $id
     * @return Subscription|null
     */
    protected function getSubscriptionByColumnAndId(string $column, string $columnFormat, $id) : ?Subscription
    {
        $tableName = CreateCommerceWebhookSubscriptionsTableAction::getTableName();
        $contextTable = CommerceTables::Contexts;

        $query = 'SELECT '.$tableName.'.*, '.$contextTable.'.'.CommerceTableColumns::GdStoreId.'
                     FROM '.$tableName.'
                         INNER JOIN '.$contextTable.' ON '.$tableName.'.'.CommerceTableColumns::CommerceContextId.' = '.$contextTable.'.id
                     WHERE '.$column.' = '.$columnFormat;

        $row = DatabaseRepository::getRow(
            $query,
            [$id]
        );

        if (! $row) {
            return null;
        }

        return Subscription::getNewInstance([
            'id'          => ArrayHelper::getStringValueForKey($row, CommerceTableColumns::SubscriptionId),
            'name'        => ArrayHelper::getStringValueForKey($row, CommerceTableColumns::Name),
            'description' => ArrayHelper::get($row, CommerceTableColumns::Description),
            'context'     => Context::getNewInstance(['storeId' => ArrayHelper::getStringValueForKey($row, CommerceTableColumns::GdStoreId)]),
            'eventTypes'  => TypeHelper::array(json_decode(ArrayHelper::getStringValueForKey($row, CommerceTableColumns::EventTypes), true), []),
            'deliveryUrl' => ArrayHelper::getStringValueForKey($row, CommerceTableColumns::DeliveryUrl),
            'isEnabled'   => ArrayHelper::getIntValueForKey($row, CommerceTableColumns::IsEnabled) === 1,
            'secret'      => ArrayHelper::getStringValueForKey($row, CommerceTableColumns::Secret),
            'createdAt'   => ArrayHelper::getStringValueForKey($row, CommerceTableColumns::CreatedAt),
            'updatedAt'   => ArrayHelper::getStringValueForKey($row, CommerceTableColumns::UpdatedAt),
        ]);
    }

    /**
     * Deletes a subscription by its remote UUID.
     *
     * @param string $subscriptionId
     * @return void
     * @throws BaseException|WordPressDatabaseException
     */
    public function deleteSubscription(string $subscriptionId) : void
    {
        $subscription = $this->getSubscriptionById($subscriptionId);
        if (! $subscription) {
            throw new BaseException('Subscription not found.');
        }

        $contextId = CommerceContextRepository::getInstance()->getContextByStoreId($subscription->context->storeId);
        if (! $contextId) {
            throw new BaseException('Context not found.');
        }

        // Clear the cache for this context ID to reflect the state of the db.
        $this->clearCache($contextId);

        DatabaseRepository::delete(
            CreateCommerceWebhookSubscriptionsTableAction::getTableName(),
            [CommerceTableColumns::SubscriptionId => $subscriptionId],
            ['%s']
        );
    }

    /**
     * Clear the cache for this context ID.
     *
     * @param int $contextId
     * @return void
     */
    public function clearCache(int $contextId) : void
    {
        WebhookSubscriptionCache::getInstance((string) $contextId)->clear();
    }
}
