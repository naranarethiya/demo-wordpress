<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Providers\Traits;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Http\Request;
use GoDaddy\WordPress\MWC\Common\Http\Response;
use GoDaddy\WordPress\MWC\Payments\Events\ProviderRequestEvent;
use GoDaddy\WordPress\MWC\Payments\Events\ProviderResponseEvent;

trait CanSendRequestWithEventsTrait
{
    /**
     * Sends a request along with firing events.
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws Exception
     */
    protected function sendRequestWithProviderEvents(Request $request) : Response
    {
        Events::broadcast(new ProviderRequestEvent($request));

        /** @var Response $response */
        $response = $request->send();

        Events::broadcast(new ProviderResponseEvent($response));

        return $response;
    }
}
