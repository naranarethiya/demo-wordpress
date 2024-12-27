<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Providers\MWC\Gateways;

use Exception;
use GoDaddy\WordPress\MWC\Common\Http\Request;
use GoDaddy\WordPress\MWC\Common\Http\Response;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Auth\Providers\Platform\Cache\Types\ErrorResponseCache;
use GoDaddy\WordPress\MWC\Core\Payments\Exceptions\AutoConnectFailedException;
use GoDaddy\WordPress\MWC\Core\Payments\Exceptions\NoAccountFoundException;
use GoDaddy\WordPress\MWC\Core\Payments\Providers\MWC\Http\Adapters\FindOrCreateOnboardingAccountRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\Providers\Traits\CanSendRequestWithEventsTrait;
use GoDaddy\WordPress\MWC\Payments\Gateways\AbstractGateway;

/**
 * Gateway for handling onboarding accounts via the MWC API.
 */
class OnboardingAccountGateway extends AbstractGateway
{
    use CanGetNewInstanceTrait;
    use CanSendRequestWithEventsTrait;

    /**
     * Finds or creates GoDaddy Payments account details for the given values.
     *
     * @return array
     * @throws Exception
     */
    public function findOrCreate() : array
    {
        ErrorResponseCache::getInstance()->clear();

        return $this->doAdaptedRequest(null, FindOrCreateOnboardingAccountRequestAdapter::getNewInstance());
    }

    /**
     * Performs a request.
     *
     * @param Request $request request object
     *
     * @return Response
     * @throws Exception
     */
    public function doRequest(Request $request) : Response
    {
        $response = $this->sendRequestWithProviderEvents($request);

        if ($response->isError()) {
            $errormessage = $response->getErrorMessage() ?? '';
            if ($response->getStatus() === 404) {
                throw new NoAccountFoundException($errormessage);
            } else {
                throw new AutoConnectFailedException($errormessage);
            }
        }

        return $response;
    }
}
