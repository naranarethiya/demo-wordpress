<?php

namespace GoDaddy\WordPress\MWC\Common\Auth\Contracts;

use GoDaddy\WordPress\MWC\Common\Auth\Exceptions\AuthProviderException;

/**
 * Authentication Provider Factory Contract.
 */
interface AuthProviderFactoryContract
{
    /**
     * Gets a new instance of AuthProviderFactoryContract.
     *
     * @return $this
     */
    public static function getNewInstance();

    /**
     * Gets the Emails Service Auth Provider.
     *
     * @return AuthProviderContract
     * @throws AuthProviderException
     */
    public function getEmailsServiceAuthProvider() : AuthProviderContract;

    /**
     * Gets the Events Auth Provider.
     *
     * @return AuthProviderContract
     * @throws AuthProviderException
     */
    public function getEventsAuthProvider() : AuthProviderContract;

    /**
     * gets the Managed WooCommerce Auth Provider.
     *
     * @return AuthProviderContract
     * @throws AuthProviderException
     */
    public function getManagedWooCommerceAuthProvider() : AuthProviderContract;
}
