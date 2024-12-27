<?php

namespace GoDaddy\WordPress\MWC\Common\Http\Traits;

use Exception;
use GoDaddy\WordPress\MWC\Common\Auth\Contracts\AuthMethodContract;
use GoDaddy\WordPress\MWC\Common\Auth\Exceptions\AuthProviderException;
use GoDaddy\WordPress\MWC\Common\Auth\Exceptions\CredentialsCreateFailedException;
use ReflectionException;

/**
 * A trait to set up an authentication method.
 */
trait CanSetAuthMethodTrait
{
    /**
     * Attempts to set up an authentication method for this request using the auth provider for the MWC API.
     *
     * @return $this
     */
    protected function tryToSetAuthMethod()
    {
        try {
            $method = $this->getAuthMethodFromAuthProvider();
        } catch (ReflectionException $exception) {
            // report an exception to Sentry but do not throw
            new AuthProviderException("A reflection exception occurred trying to get an instance of the AuthProviderFactory class: {$exception->getMessage()}", $exception);

            return $this;
        } catch (AuthProviderException $exception) {
            // the exception will be reported to Sentry automatically
            return $this;
        } catch (CredentialsCreateFailedException $exception) {
            // this exception is not meant to be reported to Sentry to avoid too many errors if an external condition
            // prevents the auth provider from retrieving valid credentials
            return $this;
        }

        return $this->setAuthMethod($method);
    }

    /**
     * Gets the authentication method from the authentication provider.
     *
     * @return AuthMethodContract
     * @throws AuthProviderException|CredentialsCreateFailedException|ReflectionException
     */
    abstract protected function getAuthMethodFromAuthProvider() : AuthMethodContract;

    /**
     * Sets the auth method for this request.
     *
     * @param AuthMethodContract $value the auth method to set
     * @return $this
     */
    abstract public function setAuthMethod(AuthMethodContract $value);
}
