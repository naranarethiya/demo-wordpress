<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Operations\Contracts;

interface DeleteWebhookSubscriptionOperationContract
{
    /**
     * Gets the ID of the subscription to be deleted.
     *
     * @return string
     */
    public function getSubscriptionId() : string;

    /**
     * Sets the ID of the subscription to delete.
     *
     * @param string $value
     * @return DeleteWebhookSubscriptionOperationContract
     */
    public function setSubscriptionId(string $value) : DeleteWebhookSubscriptionOperationContract;
}
