<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\User;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Exceptions\AssetUserCreateFailedException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Exceptions\AssetUserNotFoundException;

/**
 * Helper class for finding the user account associated with Commerce-originating assets.
 *
 * In order to better identify a local attachment DB record as being Commerce-originating, we associate them with
 * a very specific user. We use a user account here instead of metadata, so we can more performantly exclude Commerce
 * assets from queries. `post_author` is indexed, and directly on the posts table, whereas filtering by a certain
 * meta key (or lack thereof) would require a join and be less performant.
 */
class AssetUserHelper
{
    /** @var string wp_option name for the asset author ID */
    protected const ASSET_AUTHOR_ID_OPTION = 'gd_mwc_commerce_asset_author_id';

    /** @var string configuration base for assets */
    public const ASSETS_CONFIG_BASE = 'commerce.catalog.assets';

    /**
     * Gets the ID of the asset user account.
     *
     * @return int|null ID if it exists, otherwise null
     */
    public static function getAssetUserId() : ?int
    {
        return TypeHelper::int(get_option(static::ASSET_AUTHOR_ID_OPTION), 0) ?: null;
    }

    /**
     * Gets the ID of the asset user account if it exists, otherwise creates it.
     *
     * @return int
     * @throws AssetUserCreateFailedException|AssetUserNotFoundException
     */
    public static function getOrCreateUserId() : int
    {
        if ($authorId = static::getAssetUserId()) {
            return $authorId;
        }

        return static::createAssetUser();
    }

    /**
     * Creates a new asset user account.
     *
     * @return int
     * @throws AssetUserCreateFailedException|AssetUserNotFoundException
     */
    protected static function createAssetUser() : int
    {
        /*
         * Not using the User model here to create/save because that doesn't support setting a password. Not setting a
         * password throws an undefined array key error.
         * @link https://godaddy-corp.atlassian.net/browse/MWC-13491 as an example
         */
        $userId = wp_insert_user([
            'user_login'   => TypeHelper::string(Configuration::get(static::ASSETS_CONFIG_BASE.'.user.login'), ''),
            'user_pass'    => wp_generate_password(32),
            'user_email'   => TypeHelper::string(Configuration::get(static::ASSETS_CONFIG_BASE.'.user.emailAddress'), ''),
            'display_name' => TypeHelper::string(Configuration::get(static::ASSETS_CONFIG_BASE.'.user.displayName'), ''),
            'role'         => 'subscriber',
        ]);

        if (WordPressRepository::isError($userId)) {
            if ('existing_user_login' === $userId->get_error_code()) {
                // this indicates the asset user already exists! Let's get them and update the database with their ID.
                $userId = static::getAssetUserIdByHandle();
            } else {
                throw new AssetUserCreateFailedException('Failed to create asset user: '.$userId->get_error_code());
            }
        }

        $userId = TypeHelper::int($userId, 0);

        update_option(static::ASSET_AUTHOR_ID_OPTION, $userId);

        return $userId;
    }

    /**
     * Gets the ID of the asset user by their handle.
     *
     * @return int
     * @throws AssetUserNotFoundException
     */
    protected static function getAssetUserIdByHandle() : int
    {
        $handle = TypeHelper::string(Configuration::get(static::ASSETS_CONFIG_BASE.'.user.login'), '');
        $user = User::getByHandle($handle);

        if (! $user) {
            throw new AssetUserNotFoundException("Unable to find asset user with handle {$handle}");
        }

        if (! $user->getId()) {
            throw new AssetUserNotFoundException("Asset user with handle {$handle} is missing an ID.");
        }

        return $user->getId();
    }

    /**
     * Determines if the supplied user ID is the "Commerce asset user".
     *
     * @param int $userId ID of the user to check
     * @return bool
     */
    public static function isCommerceAssetUser(int $userId) : bool
    {
        // if the Commerce user doesn't even exist, then it's not a match!
        if (! $commerceUserId = static::getAssetUserId()) {
            return false;
        }

        return $userId === $commerceUserId;
    }
}
