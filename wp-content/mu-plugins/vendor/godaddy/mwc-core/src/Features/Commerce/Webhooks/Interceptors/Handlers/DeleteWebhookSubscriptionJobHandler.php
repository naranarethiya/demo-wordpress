<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Interceptors\Handlers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\Handlers\AbstractInterceptorHandler;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Interceptors\DeleteWebhookSubscriptionJobInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Operations\DeleteWebhookSubscriptionOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Services\Contracts\SubscriptionServiceContract;

/**
 * Handler for {@see DeleteWebhookSubscriptionJobInterceptor}.
 */
class DeleteWebhookSubscriptionJobHandler extends AbstractInterceptorHandler
{
    protected SubscriptionServiceContract $subscriptionService;

    public function __construct(SubscriptionServiceContract $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Deletes the provided webhook subscription.
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

            $this->subscriptionService->deleteSubscription(
                DeleteWebhookSubscriptionOperation::seed(['subscriptionId' => $subscriptionId])
            );
        } catch(Exception|CommerceExceptionContract $e) {
            SentryException::getNewInstance('Failed to delete webhook subscription: '.$e->getMessage(), $e);
        }
    }
}
