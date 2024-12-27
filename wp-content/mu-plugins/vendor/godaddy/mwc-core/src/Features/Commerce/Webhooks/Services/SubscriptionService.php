<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Services;

use GoDaddy\WordPress\MWC\Common\Http\Url;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\GatewayRequest404Exception;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\DataObjects\Context;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Operations\Contracts\CreateWebhookSubscriptionOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Operations\Contracts\DeleteWebhookSubscriptionOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Operations\CreateWebhookSubscriptionOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\Contracts\WebhookProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\DataObjects\CreateSubscriptionInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\DataObjects\DeleteSubscriptionInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\DataObjects\ListSubscriptionsInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Repositories\WebhookSubscriptionRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Services\Contracts\SubscriptionServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Services\Responses\Contracts\CreateSubscriptionResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Services\Responses\Contracts\ListSubscriptionsResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Services\Responses\CreateSubscriptionResponse;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Services\Responses\ListSubscriptionsResponse;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Traits\CanDetermineWebhookSubscriptionDeliveryUrl;

/**
 * Webhook subscription service.
 */
class SubscriptionService implements SubscriptionServiceContract
{
    use CanGetNewInstanceTrait;
    use CanDetermineWebhookSubscriptionDeliveryUrl;

    /** @var CommerceContextContract Commerce Context */
    protected CommerceContextContract $commerceContext;

    /** @var WebhookProviderContract provider to the external API's CRUD operations */
    protected WebhookProviderContract $webhookProvider;

    /** @var WebhookSubscriptionRepository repository for interacting with the local subscription database */
    protected WebhookSubscriptionRepository $webhookSubscriptionRepository;

    public function __construct(CommerceContextContract $commerceContext, WebhookProviderContract $webhookProvider, WebhookSubscriptionRepository $webhookSubscriptionRepository)
    {
        $this->commerceContext = $commerceContext;
        $this->webhookProvider = $webhookProvider;
        $this->webhookSubscriptionRepository = $webhookSubscriptionRepository;
    }

    /**
     * {@inheritDoc}
     */
    public function listSubscriptions() : ListSubscriptionsResponseContract
    {
        try {
            return $this->webhookProvider->subscriptions()->list(
                $this->getListSubscriptionsInput()
            );
        } catch(GatewayRequest404Exception $e) {
            // this indicates there are no subscriptions
            return ListSubscriptionsResponse::getNewInstance();
        }
    }

    /**
     * Gets the input for listing subscriptions.
     *
     * @return ListSubscriptionsInput
     */
    protected function getListSubscriptionsInput() : ListSubscriptionsInput
    {
        return ListSubscriptionsInput::getNewInstance([
            'storeId' => $this->commerceContext->getStoreId(),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function createSubscription(CreateWebhookSubscriptionOperationContract $operation) : CreateSubscriptionResponseContract
    {
        $input = $this->getCreateSubscriptionInput($operation);

        $subscription = $this->webhookProvider->subscriptions()->create($input);

        $this->webhookSubscriptionRepository->createSubscription($subscription);

        return new CreateSubscriptionResponse($subscription);
    }

    /**
     * Gets the input for creating a subscription.
     *
     * @param CreateWebhookSubscriptionOperationContract $operation
     * @return CreateSubscriptionInput
     */
    protected function getCreateSubscriptionInput(CreateWebhookSubscriptionOperationContract $operation) : CreateSubscriptionInput
    {
        return CreateSubscriptionInput::getNewInstance([
            'name'        => $operation->getName(),
            'description' => $operation->getDescription(),
            'deliveryUrl' => $operation->getDeliveryUrl(),
            'eventTypes'  => $operation->getEventTypes(),
            'context'     => Context::getNewInstance(['storeId' => $this->commerceContext->getStoreId()]),
            'isEnabled'   => $operation->getIsEnabled(),
        ]);
    }

    /**
     * {@inheritDoc}
     * @throws Url\Exceptions\InvalidUrlException|Url\Exceptions\InvalidUrlSchemeException
     */
    public function getCreateWebhookSubscriptionOperation(array $eventTypes) : CreateWebhookSubscriptionOperationContract
    {
        $operation = new CreateWebhookSubscriptionOperation();
        $operation->setDeliveryUrl($this->getSubscriptionDeliveryUrl());
        $operation->setEventTypes($eventTypes);

        return $operation;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteSubscription(DeleteWebhookSubscriptionOperationContract $operation) : void
    {
        $this->webhookProvider->subscriptions()->delete(
            DeleteSubscriptionInput::getNewInstance(['subscriptionId' => $operation->getSubscriptionId()])
        );

        $this->webhookSubscriptionRepository->deleteSubscription($operation->getSubscriptionId());
    }
}
