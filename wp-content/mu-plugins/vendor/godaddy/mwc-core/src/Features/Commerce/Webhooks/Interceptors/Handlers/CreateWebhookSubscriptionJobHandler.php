<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Interceptors\Handlers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Interceptors\Handlers\AbstractInterceptorHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Configuration\Contracts\CommerceWebhooksRuntimeConfigurationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Exceptions\WebhookSubscriptionCreationConflictException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Interceptors\CreateWebhookSubscriptionJobInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Operations\DeleteWebhookSubscriptionOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Services\Contracts\SubscriptionServiceContract;

/**
 * Handle creating commerce webhook subscriptions.
 * {@see CreateWebhookSubscriptionJobInterceptor}.
 */
class CreateWebhookSubscriptionJobHandler extends AbstractInterceptorHandler
{
    protected SubscriptionServiceContract $subscriptionService;
    protected CommerceWebhooksRuntimeConfigurationContract $runtimeConfiguration;

    public function __construct(SubscriptionServiceContract $subscriptionService, CommerceWebhooksRuntimeConfigurationContract $runtimeConfiguration)
    {
        $this->subscriptionService = $subscriptionService;
        $this->runtimeConfiguration = $runtimeConfiguration;
    }

    /**
     * {@inheritDoc}
     */
    public function run(...$args)
    {
        $eventTypes = $this->runtimeConfiguration->getEnabledWebhookEventTypeNames();

        if (! $eventTypes) {
            return;
        }

        try {
            // Create a new webhook subscription based on the current configuration.
            $operation = $this->subscriptionService->getCreateWebhookSubscriptionOperation($eventTypes);

            $this->subscriptionService->createSubscription($operation);
        } catch (SentryException $exception) {
            // do nothing because all SentryExceptions are automatically reported
        } catch (WebhookSubscriptionCreationConflictException $exception) {
            $this->deleteSubscription($exception);
        } catch (CommerceExceptionContract|Exception $e) {
            SentryException::getNewInstance('Failed to create Commerce webhook subscription: '.$e->getMessage(), $e);
        }
    }

    /**
     * Delete a conflicting subscription.
     *
     * @param WebhookSubscriptionCreationConflictException $exception
     */
    protected function deleteSubscription(WebhookSubscriptionCreationConflictException $exception) : void
    {
        try {
            if (empty($subscriptionId = $exception->getSubscriptionId())) {
                throw new BaseException('Missing subscription ID from conflict exception.');
            }

            $this->subscriptionService->deleteSubscription(
                DeleteWebhookSubscriptionOperation::seed(['subscriptionId' => $subscriptionId])
            );
        } catch (SentryException $exception) {
            // do nothing because all SentryExceptions are automatically reported
        } catch (CommerceExceptionContract|Exception $e) {
            SentryException::getNewInstance('Failed to delete conflicting Commerce webhook subscription: '.$e->getMessage(), $e);
        }
    }
}
