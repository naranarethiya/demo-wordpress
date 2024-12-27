<?php

namespace GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce;

use Exception;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\DatabaseRepository;
use WC_Session;

/**
 * A repository for handling the WooCommerce session.
 */
class SessionRepository
{
    /**
     * Gets the WooCommerce session instance.
     *
     * @return WC_Session
     * @throws Exception
     */
    public static function getInstance() : WC_Session
    {
        $wc = WooCommerceRepository::getInstance();

        if (! $wc || empty($wc->session) || ! $wc->session instanceof WC_Session) {
            throw new Exception(__('WooCommerce session is not available', 'mwc-common'));
        }

        return $wc->session;
    }

    /**
     * Gets a session value for a given key.
     *
     * @param string $key
     * @param mixed|null $default
     * @return array|string
     * @throws Exception
     */
    public static function get(string $key, $default = null)
    {
        return static::getInstance()->get($key, $default);
    }

    /**
     * Sets a value to session with a given key.
     *
     * @param string $key
     * @param mixed $value
     * @return WC_Session
     * @throws Exception
     */
    public static function set(string $key, $value) : WC_Session
    {
        static::getInstance()->set($key, $value);

        return static::getInstance();
    }

    /**
     * Gets the customer ID from the WooCommerce session.
     * ID is int if customer is logged in, hash if they're a guest, null in rare cases when a value cannot be found.
     *
     * @return int|string|null
     * @throws Exception
     */
    public static function getCustomerId()
    {
        $customerId = static::getInstance()->get_customer_id();

        return is_numeric($customerId) ? (int) $customerId : $customerId;
    }

    /**
     * Retrieves a session record from the database by its ID.
     *
     * @param int $sessionId
     * @return array
     */
    public static function getSessionById(int $sessionId) : array
    {
        $tablePrefix = DatabaseRepository::getTablePrefix();

        return DatabaseRepository::getRow(
            "SELECT * FROM {$tablePrefix}woocommerce_sessions WHERE session_id = %d",
            [$sessionId]
        );
    }

    /**
     * Retrieves a session record from the database by its key.
     *
     * @param string $sessionKey
     * @return array
     */
    public static function getSessionByKey(string $sessionKey) : array
    {
        $tablePrefix = DatabaseRepository::getTablePrefix();

        return DatabaseRepository::getRow(
            "SELECT * FROM {$tablePrefix}woocommerce_sessions WHERE session_key = %s",
            [$sessionKey]
        );
    }
}
