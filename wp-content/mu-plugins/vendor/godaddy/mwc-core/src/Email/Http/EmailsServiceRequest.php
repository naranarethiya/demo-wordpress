<?php

namespace GoDaddy\WordPress\MWC\Core\Email\Http;

use Exception;
use GoDaddy\WordPress\MWC\Common\Auth\AuthProviderFactory;
use GoDaddy\WordPress\MWC\Common\Auth\Exceptions\AuthProviderException;
use GoDaddy\WordPress\MWC\Common\Auth\Exceptions\CredentialsCreateFailedException;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Contracts\GraphQLOperationContract;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Http\GraphQL\Request;
use GoDaddy\WordPress\MWC\Common\Http\GraphQL\Response;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Email\Exceptions\EmailsServiceAuthProviderException;
use GoDaddy\WordPress\MWC\Core\Email\Http\GraphQL\Mutations\IssueTokenForSiteMutation;
use ReflectionException;

class EmailsServiceRequest
{
    use CanGetNewInstanceTrait;
    /* @var GraphQLOperationContract GraphQL operation that will be used in request. */
    protected $operation;

    /**
     * @param GraphQLOperationContract $operation
     * @return EmailsServiceRequest
     */
    public function setOperation(GraphQLOperationContract $operation) : EmailsServiceRequest
    {
        $this->operation = $operation;

        return $this;
    }

    /**
     * Sends request and may retry with refreshed token if response is unauthenticated.
     *
     * @return Response
     * @throws Exception
     */
    public function send() : Response
    {
        return $this->maybeRetryIfUnauthenticated($this->sendWithoutRetry());
    }

    /**
     * Sends request without retry.
     *
     * @return Response
     * @throws EmailsServiceAuthProviderException|Exception
     */
    protected function sendWithoutRetry() : Response
    {
        $request = (new Request($this->operation))
            ->setUrl($this->getApiUrl());

        if ($this->shouldAuth()) {
            try {
                $request->setAuthMethod(AuthProviderFactory::getNewInstance()->getEmailsServiceAuthProvider()->getMethod());
            } catch (ReflectionException|AuthProviderException|CredentialsCreateFailedException $exception) {
                throw new EmailsServiceAuthProviderException("Could not get authentication method: {$exception->getMessage()}", $exception);
            }
        }

        return $request->send();
    }

    /**
     * Checks response and, if response is unauthenticated and retries are allowed, may retry with refreshed token.
     *
     * @param Response $response
     * @return Response
     * @throws EmailsServiceAuthProviderException
     */
    protected function maybeRetryIfUnauthenticated(Response $response) : Response
    {
        if ($this->shouldAuth() && $this->isResponseUnauthenticatedError($response)) {
            try {
                AuthProviderFactory::getNewInstance()->getEmailsServiceAuthProvider()->deleteCredentials();
            } catch (ReflectionException|AuthProviderException $exception) {
                throw new EmailsServiceAuthProviderException("Could not delete credentials from cache: {$exception->getMessage()}", $exception);
            }

            return $this->sendWithoutRetry();
        }

        return $response;
    }

    /**
     * @return string
     * @throws Exception
     */
    protected function getApiUrl() : string
    {
        return Configuration::get('mwc.emails_service.api.url', '');
    }

    /**
     * Does this operation require an authorization header with token?
     *
     * @return bool
     */
    protected function shouldAuth() : bool
    {
        return ! is_a($this->operation, IssueTokenForSiteMutation::class);
    }

    /**
     * Is the given response from the service telling us it could not authenticate?
     *
     * @param Response $response
     * @return bool
     */
    protected function isResponseUnauthenticatedError(Response $response) : bool
    {
        $errorMessage = $response->getErrorMessage();

        return $errorMessage !== null && StringHelper::startsWith($errorMessage, 'Unauthenticated');
    }
}
