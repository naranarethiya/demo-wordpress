<?php

namespace GoDaddy\WordPress\MWC\Common\DataSources\WordPress\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\User;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use WP_User;

/**
 * Adapter to convert between a WordPress user object and a native user object.
 *
 * @method static static getNewInstance(array|User|WP_User $data)
 */
class UserAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var array<mixed> user data */
    private $data;

    /**
     * WordPress user adapter constructor.
     *
     * @param array<mixed>|User|WP_User $data user data from WP_User, User, or array of data
     */
    public function __construct($data)
    {
        if ($data instanceof WP_User) {
            /* @var WP_User $data some keys may not be available in the {@see WP_User} object's array form using to array method here */
            $this->data = array_merge($data->to_array(), [
                'user_firstname' => $data->user_firstname ?? '',
                'user_lastname'  => $data->user_lastname ?? '',
                'nickname'       => $data->nickname ?? '',
            ]);
        } elseif ($data instanceof User) {
            $this->data = $data->toArray();
        } else {
            $this->data = (array) $data;
        }
    }

    /**
     * Converts native user data to WordPress user data.
     *
     * @return array{
     *     'ID' : int,
     *     'user_email': string,
     *     'user_login': string,
     *     'user_firstname': string,
     *     'user_lastname': string,
     *     'nickname': string,
     *     'user_nicename': string,
     * }
     */
    public function convertToSource() : array
    {
        return [
            'ID'             => TypeHelper::int(ArrayHelper::get($this->data, 'id'), 0),
            'user_email'     => TypeHelper::string(ArrayHelper::get($this->data, 'email'), ''),
            'user_login'     => TypeHelper::string(ArrayHelper::get($this->data, 'handle'), ''),
            'user_firstname' => TypeHelper::string(ArrayHelper::get($this->data, 'firstName'), ''),
            'user_lastname'  => TypeHelper::string(ArrayHelper::get($this->data, 'lastName'), ''),
            'nickname'       => TypeHelper::string(ArrayHelper::get($this->data, 'nickname'), ''),
            'user_nicename'  => TypeHelper::string(ArrayHelper::get($this->data, 'displayName'), ''),
        ];
    }

    /**
     * Converts WordPress user data to native user data.
     *
     * @return array<string, mixed>
     */
    public function convertFromSource() : array
    {
        return [
            'id'          => ArrayHelper::get($this->data, 'ID', 0),
            'email'       => ArrayHelper::get($this->data, 'user_email', ''),
            'handle'      => ArrayHelper::get($this->data, 'user_login', ''),
            'firstName'   => ArrayHelper::get($this->data, 'user_firstname', ''),
            'lastName'    => ArrayHelper::get($this->data, 'user_lastname', ''),
            'nickname'    => ArrayHelper::get($this->data, 'nickname', ''),
            'displayName' => ArrayHelper::get($this->data, 'user_nicename', ''),
        ];
    }
}
