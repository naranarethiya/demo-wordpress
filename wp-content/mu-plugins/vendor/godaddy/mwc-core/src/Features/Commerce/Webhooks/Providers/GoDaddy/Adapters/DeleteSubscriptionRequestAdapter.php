<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\GoDaddy\Adapters;

use GoDaddy\WordPress\MWC\Common\Http\Contracts\RequestContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Adapters\AbstractGatewayRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Exceptions\WebhookSubscriptionOperationForbiddenException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\DataObjects\DeleteSubscriptionInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\GoDaddy\Http\Request;

/**
 * Delete Subscription Request Adapter.
 *
 * @method static static getNewInstance(DeleteSubscriptionInput $input)
 */
class DeleteSubscriptionRequestAdapter extends AbstractGatewayRequestAdapter
{
    use CanGetNewInstanceTrait;

    protected DeleteSubscriptionInput $input;

    public function __construct(DeleteSubscriptionInput $input)
    {
        $this->input = $input;
    }

    /**
     * Converts a response.
     *
     * @codeCoverageIgnore
     *
     * @param ResponseContract $response
     * @return void
     */
    protected function convertResponse(ResponseContract $response)
    {
        // no-op
        // a delete operation has no response body; it's considered successful as long as we have a 200 response
        // non-200 response codes are already checked in `throwIfIsErrorResponse()`
    }

    /**
     * Converts the delete subscription input into a gateway request.
     *
     * @return RequestContract
     */
    public function convertFromSource() : RequestContract
    {
        return Request::withAuth()
            ->setPath("/webhook-subscriptions/{$this->input->subscriptionId}")
            ->setMethod('DELETE');
    }

    /**
     * Throws an exception on error responses.
     *
     * @param ResponseContract $response
     * @return void
     * @throws CommerceExceptionContract|WebhookSubscriptionOperationForbiddenException
     */
    protected function throwIfIsErrorResponse(ResponseContract $response) : void
    {
        if ($response->isError() && 403 === $status = $response->getStatus()) {
            throw new WebhookSubscriptionOperationForbiddenException($response->getErrorMessage() ?: 'Request Error. The server responded with status: '.$status);
        }

        parent::throwIfIsErrorResponse($response);
    }
}
