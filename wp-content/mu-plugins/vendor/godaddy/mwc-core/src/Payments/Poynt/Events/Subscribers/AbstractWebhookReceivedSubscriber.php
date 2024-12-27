<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Events\Subscribers;

use GoDaddy\WordPress\MWC\Common\Events\AbstractWebhookReceivedEvent;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\SubscriberContract;
use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;

abstract class AbstractWebhookReceivedSubscriber implements SubscriberContract
{
    /**
     * Gets the resource id.
     *
     * @param array $payload
     * @return string
     */
    public function getResourceId(array $payload) : string
    {
        return (string) ($payload['resourceId'] ?? '');
    }

    /**
     * Gets the resource.
     *
     * @param string $resourceId
     * @return AbstractModel
     */
    abstract public function getResource(string $resourceId) : AbstractModel;

    /**
     * Gets the action.
     *
     * @param array $payload
     * @return string
     */
    public function getAction(array $payload) : string
    {
        if (empty($payload['eventType']) || ! strpos($payload['eventType'], '_')) {
            return '';
        }

        return array_slice(explode('_', $payload['eventType']), -1)[0];
    }

    /**
     * Handles the incoming webhook received event.
     *
     * @param EventContract $event
     */
    public function handle(EventContract $event)
    {
        if (! $event instanceof AbstractWebhookReceivedEvent) {
            return;
        }

        $payload = $event->getPayloadDecoded();
        $action = $this->getAction($payload);

        if ($this->shouldHandleAction($action)) {
            $this->handleAction(
                $action,
                $this->getResource($this->getResourceId($payload))
            );
        }
    }

    /**
     * Determines whether the supplied action should be handled.
     *
     * @param string $action
     * @return bool
     */
    protected function shouldHandleAction(string $action) : bool
    {
        return true;
    }

    /**
     * Handles the action.
     *
     * @param string $action
     * @param AbstractModel $model
     * @return void
     */
    abstract public function handleAction(string $action, AbstractModel $model);
}
