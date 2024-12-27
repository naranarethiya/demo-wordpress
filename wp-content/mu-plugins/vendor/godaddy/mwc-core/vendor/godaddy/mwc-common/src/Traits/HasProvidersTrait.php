<?php

namespace GoDaddy\WordPress\MWC\Common\Traits;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Providers\Contracts\ProviderContract;
use GoDaddy\WordPress\MWC\Common\Traits\Exceptions\InvalidProviderException;
use GoDaddy\WordPress\MWC\Common\Traits\Exceptions\MissingProviderException;

/**
 * A trait for objects that has providers.
 *
 * @method static static getInstance() may be available if the class also implements SingletonTrait
 */
trait HasProvidersTrait
{
    /** @var ProviderContract[] object providers */
    protected $providers = [];

    /**
     * Gets the providers.
     *
     * @return ProviderContract[]
     */
    public function getProviders() : array
    {
        return $this->providers;
    }

    /**
     * Determines whether a provider is registered within the class implementing the trait.
     *
     * @param string $providerName provider name
     * @return bool
     */
    public function hasProvider(string $providerName) : bool
    {
        return ArrayHelper::exists($this->providers, $providerName);
    }

    /**
     * Sets the providers from class names or instances.
     *
     * If class names are passed, any valid providers will be instantiated.
     *
     * @TODO when PHP 7.3 is supported, add a return type to this method {unfulvio 2021-06-21}
     *
     * @param string[]|ProviderContract[] $providers array of provider class names or objects
     * @return $this
     */
    public function setProviders(array $providers)
    {
        $this->providers = [];

        foreach ($providers as $provider) {
            if (is_string($provider)) {
                if (! is_subclass_of($provider, ProviderContract::class)) {
                    continue;
                }

                /** @var ProviderContract $provider */
                $provider = new $provider();
            }

            if (! is_subclass_of($provider, ProviderContract::class)) {
                continue;
            }

            $this->providers[$provider->getName()] = $provider;
        }

        return $this;
    }

    /**
     * Gets a provider, if found.
     *
     * @param string $provider
     * @return ProviderContract
     * @throws MissingProviderException
     */
    public function getProvider(string $provider) : ProviderContract
    {
        /** @var ProviderContract|null $foundProvider */
        $foundProvider = ArrayHelper::get($this->providers, $provider);

        if (! $foundProvider) {
            throw new MissingProviderException(sprintf('Provider "%s" not found.', $provider));
        }

        return $foundProvider;
    }

    /**
     * Returns a provider instance from a singleton handler implementing the trait, if found.
     *
     * This assumes the singleton handler will have loaded providers upon initialization, once.
     *
     * @returns ProviderContract
     * @throws Exception
     */
    public static function provider(string $provider) : ProviderContract
    {
        if (! ArrayHelper::exists(class_uses(__CLASS__), IsSingletonTrait::class)) {
            throw new InvalidProviderException(sprintf('To use %1$s the class %2$s must implement the %3$s trait.', __METHOD__, __CLASS__, IsSingletonTrait::class));
        }

        /* @phpstan-ignore-next-line */
        return static::getInstance()->getProvider($provider);
    }
}
