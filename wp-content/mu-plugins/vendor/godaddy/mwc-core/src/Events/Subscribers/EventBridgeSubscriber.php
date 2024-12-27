<?php

namespace GoDaddy\WordPress\MWC\Core\Events\Subscribers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventBridgeEventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\SubscriberContract;
use GoDaddy\WordPress\MWC\Common\Events\Http\GraphQL\Mutations\EventCreateMutation;
use GoDaddy\WordPress\MWC\Common\Events\Http\GraphQL\Request;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\DeprecationHelper;
use GoDaddy\WordPress\MWC\Common\Http\Response;
use GoDaddy\WordPress\MWC\Common\Models\User;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Core\Exceptions\EventBridgeEventSendFailedException;

/**
 * Event bridge subscriber.
 */
class EventBridgeSubscriber implements SubscriberContract
{
    /**
     * @param EventContract $event
     */
    public function handle(EventContract $event)
    {
        if (! $this->shouldSendEvent($event)) {
            return;
        }

        try {
            $this->sendEvent($event);
        } catch (EventBridgeEventSendFailedException $exception) {
            // If an EventBridgeEventSendFailedException exception is thrown, it
            // will automatically report itself to sentry when PHP destructs the
            // object, even if it’s caught by this try-catch above.
        }
    }

    /**
     * Determines whether the given event should be sent.
     *
     * @param EventContract $event event object
     *
     * @return bool
     */
    protected function shouldSendEvent(EventContract $event) : bool
    {
        // don't send if this is not the production environment and the plugin is not configured to send local events
        if (! ManagedWooCommerceRepository::isProductionEnvironment() && ! Configuration::get('events.send_local_events')) {
            return false;
        }

        // only send events that are an EventBridgeEventContract
        return $event instanceof EventBridgeEventContract;
    }

    /**
     * Send the Event to the streamer.
     *
     * @param EventBridgeEventContract|EventContract $event
     * @return Response
     * @throws EventBridgeEventSendFailedException
     */
    protected function sendEvent(EventContract $event) : Response
    {
        try {
            $response = $this->buildRequest($event)->send();
        } catch (Exception $exception) {
            throw new EventBridgeEventSendFailedException("An unknown error occurred trying to send an event to EventBridge. {$exception->getMessage()}", $exception);
        }

        if ($response->isError()) {
            throw new EventBridgeEventSendFailedException($response->getErrorMessage() ?: 'Unknown error');
        }

        return $response;
    }

    /**
     * Returns an EventCreateMutation for the current event and user.
     *
     * @param EventBridgeEventContract $event
     * @param User|null $user
     * @return EventCreateMutation
     */
    protected function getEventCreateMutation(EventBridgeEventContract $event, User $user = null) : EventCreateMutation
    {
        $eventMutation = new EventCreateMutation($event);

        if ($user) {
            $eventMutation->setUser($user);
        }

        return $eventMutation;
    }

    /**
     * Builds a RequestContract from the EventCreateMutation.
     *
     * @param EventBridgeEventContract $event
     * @return Request
     * @throws Exception
     */
    protected function buildRequest(EventContract $event) : Request
    {
        $eventMutation = $this->getEventCreateMutation($event);

        return Request::withAuth($eventMutation)->setSiteId(PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->getSiteId());
    }

    /**
     * Determines the user's actual IP address and attempts to partially
     * anonymize an IP address by converting it to a network ID.
     *
     * @see \WP_Community_Events::get_unsafe_client_ip()
     *
     * TODO: remove this method in 2022-11-10 or on version 4.0.0 {wvega 2022-05-10}
     *
     * @deprecated
     *
     * @return string|false
     */
    public static function getClientIp()
    {
        DeprecationHelper::deprecatedFunction(__METHOD__, '3.3.1');

        $clientIp = false;

        // in order of preference, with the best ones for this purpose first
        $addressHeaders = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        ];

        foreach ($addressHeaders as $header) {
            if (ArrayHelper::has($_SERVER, $header)) {
                /*
                 * HTTP_X_FORWARDED_FOR can contain a chain of comma-separated
                 * addresses. The first one is the original client. It can't be
                 * trusted for authenticity, but we don't need to for this purpose.
                 */
                $addressChain = explode(',', $_SERVER[$header]);
                $clientIp = trim($addressChain[0]);

                break;
            }
        }

        if (! $clientIp) {
            return false;
        }

        $anonIp = wp_privacy_anonymize_ip($clientIp, true);

        if ('0.0.0.0' === $anonIp || '::' === $anonIp) {
            return false;
        }

        return $anonIp;
    }
}
