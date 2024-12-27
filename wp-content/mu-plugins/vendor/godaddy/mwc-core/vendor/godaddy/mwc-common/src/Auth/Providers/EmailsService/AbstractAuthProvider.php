<?php

namespace GoDaddy\WordPress\MWC\Common\Auth\Providers\EmailsService;

use Exception;
use GoDaddy\WordPress\MWC\Common\Auth\Contracts\AuthMethodContract;
use GoDaddy\WordPress\MWC\Common\Auth\Exceptions\AuthProviderException;
use GoDaddy\WordPress\MWC\Common\Auth\Methods\TokenAuthMethod;
use GoDaddy\WordPress\MWC\Common\Auth\Providers\AbstractAuthProvider as BaseAbstractAuthProvider;
use GoDaddy\WordPress\MWC\Common\Auth\Providers\EmailsService\Cache\Types\ErrorResponseCache;
use GoDaddy\WordPress\MWC\Common\Auth\Providers\EmailsService\Cache\Types\TokenCache;
use GoDaddy\WordPress\MWC\Common\Auth\Providers\Models\Token;
use GoDaddy\WordPress\MWC\Common\Auth\Providers\Traits\CanBuildTokenCredentialsTrait;
use GoDaddy\WordPress\MWC\Common\Cache\Contracts\CacheableContract;
use GoDaddy\WordPress\MWC\Common\Contracts\GraphQLOperationContract;
use GoDaddy\WordPress\MWC\Common\EmailsService\Http\GraphQL\Request;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\RequestContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;

abstract class AbstractAuthProvider extends BaseAbstractAuthProvider
{
    use CanBuildTokenCredentialsTrait;

    /**
     * {@inheritDoc}
     */
    protected function getCredentialsCache() : CacheableContract
    {
        return TokenCache::getNewInstance();
    }

    /**
     * {@inheritDoc}
     */
    protected function getCredentialsErrorCache() : CacheableContract
    {
        return ErrorResponseCache::getNewInstance();
    }

    /**
     * Gets the credentials request.
     *
     * @return RequestContract
     * @throws Exception
     */
    protected function getCredentialsRequest() : RequestContract
    {
        return new Request($this->getIssueTokenForSiteMutation());
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
     * Gets an issue site token GraphQL mutation operation.
     *
     * @return GraphQLOperationContract
     */
    abstract protected function getIssueTokenForSiteMutation() : GraphQLOperationContract;

    /**
     * @throws AuthProviderException
     */
    protected function getCredentialsData(ResponseContract $response) : array
    {
        if (! $accessToken = ArrayHelper::get(parent::getCredentialsData($response), 'data.issueTokenForSite')) {
            throw new AuthProviderException('The response does not include a token for the site.');
        }

        return ['accessToken' => $accessToken];
    }
}
