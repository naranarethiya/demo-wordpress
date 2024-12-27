<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Http\Traits;

use GoDaddy\WordPress\MWC\Common\Auth\AuthProviderFactory;
use GoDaddy\WordPress\MWC\Common\Auth\Contracts\AuthMethodContract;
use GoDaddy\WordPress\MWC\Common\Auth\Exceptions\AuthProviderException;
use GoDaddy\WordPress\MWC\Common\Auth\Exceptions\CredentialsCreateFailedException;

trait HasManagedWooCommerceAuthProviderTrait
{
    /**
     * Gets the authentication method from the authentication provider.
     *
     * @return AuthMethodContract
     * @throws AuthProviderException|CredentialsCreateFailedException
     */
    protected function getAuthMethodFromAuthProvider() : AuthMethodContract
    {
        return AuthProviderFactory::getNewInstance()->getManagedWooCommerceAuthProvider()->getMethod();
    }
}
