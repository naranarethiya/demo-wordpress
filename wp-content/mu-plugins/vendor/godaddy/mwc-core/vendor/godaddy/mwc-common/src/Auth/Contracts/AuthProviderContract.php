<?php

namespace GoDaddy\WordPress\MWC\Common\Auth\Contracts;

use GoDaddy\WordPress\MWC\Common\Auth\Exceptions\CredentialsCreateFailedException;

/**
 * Authentication Provider Contract.
 */
interface AuthProviderContract
{
    /**
     * Retrieves the authentication method.
     *
     * @return AuthMethodContract
     * @throws CredentialsCreateFailedException
     */
    public function getMethod() : AuthMethodContract;

    /**
     * Retrieves the credentials.
     *
     * @return AuthCredentialsContract
     * @throws CredentialsCreateFailedException
     */
    public function getCredentials() : AuthCredentialsContract;

    /**
     * Deletes the credentials, generally stored in the cache.
     *
     * @return void
     */
    public function deleteCredentials() : void;
}
