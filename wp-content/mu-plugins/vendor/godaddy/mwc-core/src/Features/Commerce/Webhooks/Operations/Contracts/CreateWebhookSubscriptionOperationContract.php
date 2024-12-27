<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Operations\Contracts;

interface CreateWebhookSubscriptionOperationContract
{
    /**
     * Gets the name of the subscription.
     *
     * @return string
     */
    public function getName() : string;

    /**
     * Sets the name of the subscription.
     *
     * @param string $value
     * @return CreateWebhookSubscriptionOperationContract
     */
    public function setName(string $value) : CreateWebhookSubscriptionOperationContract;

    /**
     * Gets the subscription's description.
     *
     * @return string
     */
    public function getDescription() : string;

    /**
     * Sets the subscription's description.
     *
     * @param string $value
     * @return CreateWebhookSubscriptionOperationContract
     */
    public function setDescription(string $value) : CreateWebhookSubscriptionOperationContract;

    /**
     * Gets the webhook delivery URL.
     *
     * @return string
     */
    public function getDeliveryUrl() : string;

    /**
     * Sets the webhook delivery URL.
     *
     * @param string $value
     * @return CreateWebhookSubscriptionOperationContract
     */
    public function setDeliveryUrl(string $value) : CreateWebhookSubscriptionOperationContract;

    /**
     * Gets the list of event types to subscribe to.
     *
     * @return string[]
     */
    public function getEventTypes() : array;

    /**
     * Sets the event types to subscribe to.
     *
     * @param string[] $value
     * @return CreateWebhookSubscriptionOperationContract
     */
    public function setEventTypes(array $value) : CreateWebhookSubscriptionOperationContract;

    /**
     * Gets whether the subscription is enabled.
     *
     * @return bool
     */
    public function getIsEnabled() : bool;

    /**
     * Sets whether the subscription is enabled.
     *
     * @param bool $value
     * @return CreateWebhookSubscriptionOperationContract
     */
    public function setIsEnabled(bool $value) : CreateWebhookSubscriptionOperationContract;
}
