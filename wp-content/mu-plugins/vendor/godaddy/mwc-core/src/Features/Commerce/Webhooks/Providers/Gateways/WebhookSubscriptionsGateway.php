<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\Gateways;

use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Gateways\AbstractGateway;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\Contracts\WebhookSubscriptionsGatewayContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\DataObjects\CreateSubscriptionInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\DataObjects\DeleteSubscriptionInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\DataObjects\ListSubscriptionsInput;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\DataObjects\Subscription;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\GoDaddy\Adapters\CreateSubscriptionRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\GoDaddy\Adapters\DeleteSubscriptionRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Providers\GoDaddy\Adapters\ListSubscriptionsRequestAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Services\Responses\Contracts\ListSubscriptionsResponseContract;

/**
 * Webhook subscription gateway.
 */
class WebhookSubscriptionsGateway extends AbstractGateway implements WebhookSubscriptionsGatewayContract
{
    use CanGetNewInstanceTrait;

    /**
     * {@inheritDoc}
     */
    public function create(CreateSubscriptionInput $input) : Subscription
    {
        /** @var Subscription $result */
        $result = $this->doAdaptedRequest(CreateSubscriptionRequestAdapter::getNewInstance($input));

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function list(ListSubscriptionsInput $input) : ListSubscriptionsResponseContract
    {
        /** @var ListSubscriptionsResponseContract $result */
        $result = $this->doAdaptedRequest(ListSubscriptionsRequestAdapter::getNewInstance($input));

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(DeleteSubscriptionInput $input) : void
    {
        $this->doAdaptedRequest(DeleteSubscriptionRequestAdapter::getNewInstance($input));
    }
}
