<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Interceptors\Handlers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\Handlers\AbstractInterceptorHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Configuration\Contracts\CommerceWebhooksRuntimeConfigurationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Interceptors\UpdateWebhookSubscriptionJobInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Operations\DeleteWebhookSubscriptionOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Services\Contracts\SubscriptionServiceContract;

/**
 * Handler for {@see UpdateWebhookSubscriptionJobInterceptor}.
 */
class UpdateWebhookSubscriptionJobHandler extends AbstractInterceptorHandler
{
    protected SubscriptionServiceContract $subscriptionService;
    protected CommerceWebhooksRuntimeConfigurationContract $runtimeConfiguration;

    public function __construct(SubscriptionServiceContract $subscriptionService, CommerceWebhooksRuntimeConfigurationContract $runtimeConfiguration)
    {
        $this->subscriptionService = $subscriptionService;
        $this->runtimeConfiguration = $runtimeConfiguration;
    }

    /**
     * Updates an existing subscription by deleting and recreating it.
     *
     * @param ...$args
     * @return void
     */
    public function run(...$args)
    {
        try {
            $subscriptionId = TypeHelper::string(ArrayHelper::get($args, 0), '');

            if (empty($subscriptionId)) {
                throw new Exception('Missing subscription ID from job arguments.');
            }

            // The API does not yet support updating subscriptions, so instead we have to delete and recreate.
            $this->deleteSubscription($subscriptionId);
            $this->createSubscription();
        } catch (Exception|CommerceExceptionContract $e) {
            SentryException::getNewInstance('Failed to update webhook subscription: '.$e->getMessage(), $e);
        }
    }

    /**
     * The API does not yet support updating subscriptions, so instead we have to delete and recreate.
     *
     * @param string $subscriptionId
     * @return void
     * @throws CommerceExceptionContract|BaseException|Exception
     */
    protected function deleteSubscription(string $subscriptionId) : void
    {
        $this->subscriptionService->deleteSubscription(DeleteWebhookSubscriptionOperation::seed(['subscriptionId' => $subscriptionId]));
    }

    /**
     * Creates a new webhook subscription.
     *
     * @return void
     * @throws BaseException|CommerceExceptionContract|Exception
     */
    protected function createSubscription() : void
    {
        $eventTypes = $this->runtimeConfiguration->getEnabledWebhookEventTypeNames();

        if (! $eventTypes) {
            throw new BaseException('Webhook event types are unexpectedly empty.');
        }

        $this->subscriptionService->createSubscription(
            $this->subscriptionService->getCreateWebhookSubscriptionOperation($eventTypes)
        );
    }
}
