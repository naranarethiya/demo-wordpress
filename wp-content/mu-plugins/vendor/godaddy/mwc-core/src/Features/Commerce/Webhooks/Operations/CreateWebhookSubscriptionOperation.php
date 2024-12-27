<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Operations;

use GoDaddy\WordPress\MWC\Common\Traits\CanSeedTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Operations\Contracts\CreateWebhookSubscriptionOperationContract;

/**
 * An operation for creating a new webhook subscription.
 */
class CreateWebhookSubscriptionOperation implements CreateWebhookSubscriptionOperationContract
{
    use CanSeedTrait;

    /** @var string name of the subscription */
    protected string $name = 'All Commerce Webhooks';

    /** @var string description of the subscription */
    protected string $description = 'A subscription to all available commerce webhooks.';

    /** @var string delivery URL for the webhooks */
    protected string $deliveryUrl;

    /** @var string[] registered event types */
    protected array $eventTypes;

    /** @var bool whether the subscription is enabled */
    protected bool $isEnabled = true;

    /** {@inheritDoc} */
    public function getName() : string
    {
        return $this->name;
    }

    /** {@inheritDoc} */
    public function setName(string $value) : CreateWebhookSubscriptionOperationContract
    {
        $this->name = $value;

        return $this;
    }

    /** {@inheritDoc} */
    public function getDescription() : string
    {
        return $this->description;
    }

    /** {@inheritDoc} */
    public function setDescription(string $value) : CreateWebhookSubscriptionOperationContract
    {
        $this->description = $value;

        return $this;
    }

    /** {@inheritDoc} */
    public function getDeliveryUrl() : string
    {
        return $this->deliveryUrl;
    }

    /** {@inheritDoc} */
    public function setDeliveryUrl(string $value) : CreateWebhookSubscriptionOperationContract
    {
        $this->deliveryUrl = $value;

        return $this;
    }

    /** {@inheritDoc} */
    public function getEventTypes() : array
    {
        return $this->eventTypes;
    }

    /** {@inheritDoc} */
    public function setEventTypes(array $value) : CreateWebhookSubscriptionOperationContract
    {
        $this->eventTypes = $value;

        return $this;
    }

    /** {@inheritDoc} */
    public function getIsEnabled() : bool
    {
        return $this->isEnabled;
    }

    /** {@inheritDoc} */
    public function setIsEnabled(bool $value) : CreateWebhookSubscriptionOperationContract
    {
        $this->isEnabled = $value;

        return $this;
    }
}
