<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\GoDaddy\Adapters;

use GoDaddy\WordPress\MWC\Common\Container\ContainerFactory;
use GoDaddy\WordPress\MWC\Common\Container\Exceptions\ContainerException;
use GoDaddy\WordPress\MWC\Common\Container\Exceptions\EntryNotFoundException;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\RequestContract;
use GoDaddy\WordPress\MWC\Common\Http\Contracts\ResponseContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Adapters\AbstractGatewayRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Exceptions\WebhookSubscriptionCreationConflictException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Exceptions\WebhookSubscriptionOperationForbiddenException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\DataObjects\CreateSubscriptionInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\DataObjects\Subscription;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\DataSources\Adapters\SubscriptionAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\GoDaddy\Http\Request;

/**
 * Create Subscription Request Adapter.
 *
 * @method static CreateSubscriptionRequestAdapter getNewInstance(CreateSubscriptionInput $input)
 */
class CreateSubscriptionRequestAdapter extends AbstractGatewayRequestAdapter
{
    use CanGetNewInstanceTrait;

    protected CreateSubscriptionInput $input;

    public function __construct(CreateSubscriptionInput $input)
    {
        $this->input = $input;
    }

    /**
     * Converts a create subscription response into a {@see Subscription} object.
     *
     * @param ResponseContract $response
     * @return Subscription
     * @throws ContainerException|EntryNotFoundException
     */
    protected function convertResponse(ResponseContract $response) : Subscription
    {
        $data = ArrayHelper::get(ArrayHelper::wrap($response->getBody()), 'subscription', []);

        /** @var SubscriptionAdapter $subscriptionAdapter */
        $subscriptionAdapter = ContainerFactory::getInstance()->getSharedContainer()->get(SubscriptionAdapter::class);

        return $subscriptionAdapter->convertToSourceFromArray(TypeHelper::array($data, []));
    }

    /**
     * Throws an exception on error responses.
     *
     * @param ResponseContract $response
     * @return void
     * @throws CommerceExceptionContract|WebhookSubscriptionOperationForbiddenException|BaseException
     */
    protected function throwIfIsErrorResponse(ResponseContract $response) : void
    {
        $status = $response->getStatus();
        $isError = $response->isError();

        if ($isError && 403 === $status) {
            throw new WebhookSubscriptionOperationForbiddenException($response->getErrorMessage() ?: 'Request Error. The server responded with status: '.$status);
        }

        if ($isError && 409 === $status) {
            throw new WebhookSubscriptionCreationConflictException($response->getErrorMessage() ?: 'Request Error. The server responded with status: '.$status);
        }

        parent::throwIfIsErrorResponse($response);
    }

    /**
     * Converts the create subscription input into a gateway request.
     *
     * @return RequestContract
     */
    public function convertFromSource() : RequestContract
    {
        $body = [
            'name'        => $this->input->name,
            'description' => $this->input->description,
            'eventTypes'  => $this->input->eventTypes,
            'context'     => [
                'storeId' => $this->input->context->storeId,
            ],
            'deliveryUrl' => $this->input->deliveryUrl,
            'isEnabled'   => $this->input->isEnabled,
        ];

        return Request::withAuth()
            ->setStoreId($this->input->context->storeId)
            ->setBody($body)
            ->setPath('/webhook-subscriptions')
            ->setMethod('post');
    }
}
