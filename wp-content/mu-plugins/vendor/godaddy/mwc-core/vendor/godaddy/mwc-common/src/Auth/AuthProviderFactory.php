<?php

namespace GoDaddy\WordPress\MWC\Common\Auth;

use GoDaddy\WordPress\MWC\Common\Auth\Contracts\AuthProviderContract;
use GoDaddy\WordPress\MWC\Common\Auth\Contracts\AuthProviderFactoryContract;
use GoDaddy\WordPress\MWC\Common\Auth\Exceptions\AuthProviderException;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

/**
 * Authentication provider factory.
 */
class AuthProviderFactory implements AuthProviderFactoryContract
{
    use CanGetNewInstanceTrait;

    /** @var string */
    protected $baseConfigurationName = 'providers.auth.godaddy.mwc';

    /** @var AuthProviderContract[] */
    protected $providers = [];

    /**
     * Gets the configured authentication provider for the given service.
     *
     * @param string $service
     * @return AuthProviderContract
     * @throws AuthProviderException
     */
    protected function getAuthProvider(string $service) : AuthProviderContract
    {
        /** @var class-string<AuthProviderContract>|null $providerClass */
        $providerClass = Configuration::get("{$this->baseConfigurationName}.{$service}");

        if (empty($providerClass)) {
            throw new AuthProviderException("Could not find an authentication provider for service: {$service}.");
        }

        $this->validateAuthProviderClass($providerClass, $service);

        /** @var AuthProviderContract|null $provider */
        $provider = ArrayHelper::get($this->providers, TypeHelper::string($providerClass, ''));

        if ($provider) {
            return $provider;
        }

        /** @var AuthProviderContract $provider */
        $provider = new $providerClass;

        ArrayHelper::set($this->providers, $providerClass, $provider);

        return $provider;
    }

    /**
     * Checks if the given class is a valid authentication provider class.
     *
     * @param class-string $className
     * @param string $service
     * @return void
     * @throws AuthProviderException
     */
    protected function validateAuthProviderClass(string $className, string $service) : void
    {
        if (! class_exists($className)) {
            throw new AuthProviderException("The authentication provider class for {$service} doesn't exist.");
        }

        if (! ArrayHelper::contains(TypeHelper::arrayOfStrings(class_implements($className)), AuthProviderContract::class)) {
            throw new AuthProviderException("Invalid authentication provider for service: {$service}. {$className} must implement AuthProviderContract.");
        }
    }

    /**
     * Gets the authentication provider for the emails service.
     *
     * @return AuthProviderContract
     * @throws AuthProviderException
     */
    public function getEmailsServiceAuthProvider() : AuthProviderContract
    {
        return $this->getAuthProvider('emails_service');
    }

    /**
     * Gets the authentication provider for events.
     *
     * @return AuthProviderContract
     * @throws AuthProviderException
     */
    public function getEventsAuthProvider() : AuthProviderContract
    {
        return $this->getAuthProvider('events_api');
    }

    /**
     * Gets the authentication provider for managed WooCommerce.
     *
     * @return AuthProviderContract
     * @throws AuthProviderException
     */
    public function getManagedWooCommerceAuthProvider() : AuthProviderContract
    {
        return $this->getAuthProvider('api');
    }

    /**
     * Gets the authentication provider for GoDaddy Marketplaces.
     *
     * @return AuthProviderContract
     * @throws AuthProviderException
     */
    public function getMarketplacesAuthProvider() : AuthProviderContract
    {
        return $this->getAuthProvider('marketplaces');
    }

    /**
     * Gets the authentication provider for WooSaaS API (Pagely Management API).
     *
     * @return AuthProviderContract
     * @throws AuthProviderException
     */
    public function getWooSaaSAuthProvider() : AuthProviderContract
    {
        return $this->getAuthProvider('woosaas');
    }
}
