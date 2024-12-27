<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Register\Types\RegisterFilter;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\DatabaseRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers\AssetUserHelper;
use WP_Error;
use WP_User;
use WP_User_Query;

/**
 * Handles behaviors related to the asset user.
 */
class AssetUserInterceptor extends AbstractInterceptor
{
    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function addHooks() : void
    {
        Register::filter()
            ->setGroup('authenticate')
            ->setPriority(PHP_INT_MAX)
            ->setHandler([$this, 'preventAssetUserLogin'])
            ->setArgumentsCount(1)
            ->execute();

        Register::filter()
            ->setGroup('user_has_cap')
            ->setPriority(PHP_INT_MAX)
            ->setHandler([$this, 'maybeRemoveUserCapabilities'])
            ->setArgumentsCount(4)
            ->execute();

        Register::filter()
            ->setGroup('users_pre_query')
            ->setPriority(PHP_INT_MAX)
            ->setHandler([$this, 'excludeAssetUserFromUserQueries'])
            ->setArgumentsCount(2)
            ->execute();

        $this->getPreCountGetUsersFilter()->execute();
    }

    /**
     * Gets the pre_count_users filter object.
     *
     * @return RegisterFilter
     */
    protected function getPreCountGetUsersFilter() : RegisterFilter
    {
        return Register::filter()
            ->setGroup('pre_count_users')
            ->setPriority(PHP_INT_MAX)
            ->setHandler([$this, 'decrementUserCountsForListTable'])
            ->setArgumentsCount(3);
    }

    /**
     * Prevents logging into the asset user account.
     *
     * @param null|WP_User|WP_Error $user
     * @return mixed
     */
    public function preventAssetUserLogin($user)
    {
        // Only force a null return to the 'authenticate' filter if $user has been
        // established and is not a WP_Error or null.
        if ($user instanceof WP_User && AssetUserHelper::isCommerceAssetUser($user->ID)) {
            return null;
        }

        return $user;
    }

    /**
     * Removes the delete_users and edit_users capabilities when trying to perform
     * actions on the asset user.
     *
     * @param array<string, bool> $allCaps
     * @param string[]|mixed $caps
     * @param mixed[]|mixed $args
     * @param WP_User|mixed $user
     * @return array<string, bool> The filtered capabilities.
     */
    public function maybeRemoveUserCapabilities($allCaps, $caps, $args, $user)
    {
        $allCaps = ArrayHelper::wrap($allCaps);

        $requestedCapability = TypeHelper::string(ArrayHelper::get($args, 0), '');
        $userIdToDelete = TypeHelper::int(ArrayHelper::get($args, 2), 0);

        // if not attempting to perform edit/delete actions on asset user or there's no user ID to target, bail.
        if (! in_array($requestedCapability, ['delete_user', 'edit_user'], true) || $userIdToDelete == 0) {
            return $allCaps;
        }

        if (AssetUserHelper::isCommerceAssetUser($userIdToDelete)) {
            $allCaps['delete_users'] = false;
            $allCaps['edit_users'] = false; // also covers hiding the "send password reset" link
        }

        return $allCaps;
    }

    /**
     * Excludes the asset user from user queries.
     *
     * @param array<mixed>|null $results
     * @param WP_User_Query $userQuery user query passed by reference
     * @return array<mixed>|null
     */
    public function excludeAssetUserFromUserQueries(?array $results, WP_User_Query $userQuery) : ?array
    {
        if ($this->shouldHideAssetUser()) {
            $this->maybeModifyQueryWhereToExcludeAssetUser($userQuery);
        }

        return $results;
    }

    /**
     * Maybe modifies the query_where property of the WP_User_Query to exclude the asset user.
     *
     * @param WP_User_Query $userQuery
     * @return void
     */
    protected function maybeModifyQueryWhereToExcludeAssetUser(WP_User_Query $userQuery) : void
    {
        if ($assetUserId = AssetUserHelper::getAssetUserId()) {
            $wpdb = DatabaseRepository::instance();

            /* @phpstan-ignore-next-line */
            $userQuery->query_where .= $wpdb->prepare(" AND {$wpdb->users}.ID != %d", $assetUserId);
        }
    }

    /**
     * Decrements user counts for the users list table when hiding the asset user.
     *
     * @param null|array<string, mixed> $result
     * @param string $countStrategy
     * @param int $siteId
     * @return null|array<string, mixed>
     */
    public function decrementUserCountsForListTable(?array $result, string $countStrategy, int $siteId) : ?array
    {
        if (! $this->shouldDecrementUserCounts($countStrategy)) {
            return $result;
        }

        $filter = $this->getPreCountGetUsersFilter();

        try {
            // Prevent recursion by disabling the filter.
            $filter->deregister();
        } catch(Exception $e) {
            return $result;
        }

        $userCounts = $this->getModifiedUserRoleCounts($siteId);

        try {
            // Re-enable the filter.
            $filter->execute();
        } catch(Exception $e) {
            // catch all exceptions in the hook callback.
        }

        return $userCounts;
    }

    /**
     * Determines if the user counts should be decremented.
     *
     * @param string $countStrategy
     * @return bool
     */
    protected function shouldDecrementUserCounts(string $countStrategy) : bool
    {
        return 'time' === $countStrategy && $this->shouldHideAssetUser();
    }

    /**
     * Gets the modified role counts array for the users list table.
     *
     * See {@see count_users()}
     *
     * @param int $siteId
     * @return array<string, int>
     */
    protected function getModifiedUserRoleCounts(int $siteId) : array
    {
        $userCounts = count_users('time', $siteId);

        $totalUsers = TypeHelper::int(ArrayHelper::get($userCounts, 'total_users'), 0);
        $totalSubscribers = TypeHelper::int(ArrayHelper::get($userCounts, 'avail_roles.subscriber'), 0);

        ArrayHelper::set($userCounts, 'total_users', $this->decrementCountToMinimumZero($totalUsers));
        ArrayHelper::set($userCounts, 'avail_roles.subscriber', $this->decrementCountToMinimumZero($totalSubscribers));

        return $userCounts;
    }

    /**
     * Decrements a count to a minimum of zero.
     *
     * @param int $count
     * @return int
     */
    protected function decrementCountToMinimumZero(int $count) : int
    {
        return max(--$count, 0);
    }

    /**
     * Determines whether the asset user should be hidden.
     *
     * @return bool
     */
    protected function shouldHideAssetUser() : bool
    {
        return TypeHelper::bool(Configuration::get(AssetUserHelper::ASSETS_CONFIG_BASE.'.hideAssetUser'), false);
    }
}
