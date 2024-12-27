<?php

namespace GoDaddy\WordPress\MWC\Common\Auth\Providers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Auth\Contracts\AuthCredentialsContract;
use GoDaddy\WordPress\MWC\Common\Auth\Contracts\AuthProviderContract;
use GoDaddy\WordPress\MWC\Common\Auth\Exceptions\AuthProviderException;
use GoDaddy\WordPress\MWC\Common\Auth\Exceptions\CredentialsCreateFailedException;
use GoDaddy\WordPress\MWC\Common\Cache\Contracts\CacheableContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\RequestContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;

/**
 * Abstract auth provider class.
 */
abstract class AbstractAuthProvider implements AuthProviderContract
{
    /**
     * get Credentials stored in cache.
     *
     * @return CacheableContract
     */
    abstract protected function getCredentialsCache() : CacheableContract;

    /**
     * get Credentials Error stored in cache.
     *
     * @return CacheableContract
     */
    abstract protected function getCredentialsErrorCache() : CacheableContract;

    /**
     * build Credentials.
     *
     * @param array $data
     * @return AuthCredentialsContract
     */
    abstract protected function buildCredentials(array $data) : AuthCredentialsContract;

    /**
     * get Credentials Request.
     *
     * @return RequestContract
     */
    abstract protected function getCredentialsRequest() : RequestContract;

    /**
     * get Credentials.
     *
     * @return AuthCredentialsContract
     * @throws CredentialsCreateFailedException
     */
    public function getCredentials() : AuthCredentialsContract
    {
        return $this->getCredentialsFromCache() ?? $this->requestCredentialsWithErrorCache();
    }

    /**
     * Deletes cached token.
     *
     * @return void
     */
    public function deleteCredentials() : void
    {
        $this->getCredentialsCache()->clear();
    }

    /**
     * Gets credentials from cache.
     *
     * @return AuthCredentialsContract
     */
    protected function getCredentialsFromCache() : ?AuthCredentialsContract
    {
        $cache = $this->getCredentialsCache()->get();

        if (empty($cache)) {
            return null;
        }

        return $this->buildCredentials(ArrayHelper::wrap($cache));
    }

    /**
     * Gets credentials data from response contract.
     *
     * @param ResponseContract $response
     * @return array
     */
    protected function getCredentialsData(ResponseContract $response) : array
    {
        return ArrayHelper::wrap($response->getBody());
    }

    /**
     * Updates credentials stored in cache.
     *
     * @param AuthCredentialsContract $credentials
     * @return void
     */
    protected function updateCredentialsCache(AuthCredentialsContract $credentials) : void
    {
        $this->getCredentialsCache()->set($credentials->toArray());
    }

    /**
     * Requests credentials with error from cache.
     *
     * @return AuthCredentialsContract
     * @throws CredentialsCreateFailedException
     */
    protected function requestCredentialsWithErrorCache() : AuthCredentialsContract
    {
        if ($errorMessage = $this->getCredentialsErrorCache()->get()) {
            throw new CredentialsCreateFailedException('Could not create credentials: '.TypeHelper::string($errorMessage, ''));
        }

        try {
            return $this->requestCredentials();
        } catch (AuthProviderException $exception) {
            $this->updateCredentialsErrorCache($exception);

            throw new CredentialsCreateFailedException("Could not create credentials: {$exception->getMessage()}", $exception);
        }
    }

    /**
     * Stores an error that occurred trying to get credentials in the error cache.
     *
     * @param AuthProviderException $exception
     */
    protected function updateCredentialsErrorCache(AuthProviderException $exception) : void
    {
        $this->getCredentialsErrorCache()->set($exception->getMessage());
    }

    /**
     * Fetches credentials, and stores those credentials in the cache.
     *
     * @return AuthCredentialsContract
     * @throws AuthProviderException
     */
    protected function requestCredentials() : AuthCredentialsContract
    {
        try {
            $response = $this->getCredentialsRequest()->send();
        } catch (Exception $exception) {
            throw new AuthProviderException("An unknown error occurred trying to get credentials. {$exception->getMessage()}", $exception);
        }

        $this->validateCredentialsResponse($response);

        $credentials = $this->buildCredentials($this->getCredentialsData($response));

        $this->updateCredentialsCache($credentials);

        return $credentials;
    }

    /**
     * Validates the given response against potential issues.
     *
     * @param ResponseContract $response
     * @return void
     * @throws AuthProviderException
     */
    protected function validateCredentialsResponse(ResponseContract $response) : void
    {
        $this->validateResponseAuthorization($response);
        $this->validateIsErrorResponse($response);
    }

    /**
     * Validates the given response doesn't have "Not Authorized 403" error.
     *
     * @param ResponseContract $response
     * @return void
     * @throws AuthProviderException
     */
    protected function validateResponseAuthorization(ResponseContract $response) : void
    {
        if ($response->getStatus() === 403) {
            throw new AuthProviderException(sprintf(
                'Not authorized to get credentials: %s',
                ArrayHelper::get($response->getBody(), 'message', 'Forbidden')
            ));
        }
    }

    /**
     * Validates the given response is an error response.
     *
     * @param ResponseContract $response
     * @return void
     * @throws AuthProviderException
     */
    protected function validateIsErrorResponse(ResponseContract $response) : void
    {
        if ($response->isError()) {
            throw new AuthProviderException("API responded with status {$response->getStatus()}, error: {$response->getErrorMessage()}");
        }
    }
}
