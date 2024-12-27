<?php

namespace GoDaddy\WordPress\MWC\Common\Auth\Providers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Auth\Contracts\AuthCredentialsContract;
use GoDaddy\WordPress\MWC\Common\Auth\Contracts\AuthMethodContract;
use GoDaddy\WordPress\MWC\Common\Auth\Exceptions\AuthProviderException;
use GoDaddy\WordPress\MWC\Common\Auth\Methods\TokenAuthMethod;
use GoDaddy\WordPress\MWC\Common\Auth\Providers\Models\Token;
use GoDaddy\WordPress\MWC\Common\Auth\Providers\Traits\CanBuildTokenCredentialsTrait;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\RequestContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Common\Http\Request;
use GoDaddy\WordPress\MWC\Common\Models\User;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;

abstract class AbstractTokenAuthProvider extends AbstractAuthProvider
{
    use CanBuildTokenCredentialsTrait;

    /**
     * {@inheritDoc}
     */
    protected function updateCredentialsCache(AuthCredentialsContract $credentials) : void
    {
        $expires = $this->getCredentialsExpiration($credentials);
        $this->getCredentialsCache()->expires($expires)->set($credentials->toArray());
    }

    /**
     * Get Token Expiration. If the token expires in less than 5 minutes, returns the minimum expiration allowed:
     * 1 second (0 would cause it to never expire, negative expiration is not allowed by redis).
     *
     * @param Token $credentials
     */
    protected function getCredentialsExpiration(AuthCredentialsContract $credentials) : int
    {
        return max($credentials->getExpiresIn() - 300, 1);
    }

    /**
     * Get Credentials Request.
     *
     * @return RequestContract
     * @throws Exception
     */
    protected function getCredentialsRequest() : RequestContract
    {
        return $this->getCredentialsRequestInstance()
            ->setUrl($this->getCredentialsRequestUrl())
            ->setMethod('POST')
            ->setBody([
                'siteId' => PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->getSiteId(),
                'userId' => $this->getCurrentUserId() ?? 0,
            ]);
    }

    /**
     * A request instance with auth, which we'll fill with other attributes later.
     *
     * @return Request
     */
    abstract protected function getCredentialsRequestInstance() : Request;

    /**
     * Gets current logged-in user ID.
     *
     * @return int|null
     */
    protected function getCurrentUserId() : ?int
    {
        $user = User::getCurrent();

        return $user ? $user->getId() : null;
    }

    /**
     * {@inheritDoc}
     */
    public function getMethod() : AuthMethodContract
    {
        /** @var $credentials Token */
        $credentials = $this->getCredentials();

        return (new TokenAuthMethod())
            ->setToken($credentials->getAccessToken())
            ->setType($credentials->getTokenType());
    }

    /**
     * {@inheritDoc}
     * @throws AuthProviderException
     */
    protected function getCredentialsData(ResponseContract $response) : array
    {
        $data = parent::getCredentialsData($response);

        if (! ArrayHelper::has($data, 'accessToken')) {
            throw new AuthProviderException('The response does not include an access token.');
        }

        return $data;
    }

    /**
     * @return string
     */
    protected function getCredentialsRequestUrl() : string
    {
        return StringHelper::trailingSlash(ManagedWooCommerceRepository::getApiUrl()).'token';
    }
}
