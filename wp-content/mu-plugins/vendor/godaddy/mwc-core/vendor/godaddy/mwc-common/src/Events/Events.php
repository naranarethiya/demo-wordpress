<?php

namespace GoDaddy\WordPress\MWC\Common\Events;

use Exception;
use GoDaddy\WordPress\MWC\Common\Container\ContainerFactory;
use GoDaddy\WordPress\MWC\Common\Container\Exceptions\ContainerException;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\SubscriberContract;
use GoDaddy\WordPress\MWC\Common\Events\Exceptions\EventBroadcastFailedException;
use GoDaddy\WordPress\MWC\Common\Events\Exceptions\EventTransformFailedException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\ConfigHelper;
use InvalidArgumentException;

/**
 * Event Handler.
 */
class Events
{
    /**
     * Broadcast one or more events.
     *
     * @param EventContract|EventContract[] $events an array of events
     */
    public static function broadcast($events)
    {
        foreach (ArrayHelper::wrap($events) as $event) {
            try {
                static::broadcastEvent($event);
            } catch (EventTransformFailedException $exception) {
                // do nothing - the exception will be automatically reported to Sentry
            } catch (Exception $exception) {
                // no need to throw because new instances of EmailSendFailedException are automatically reported
                new EventBroadcastFailedException($exception->getMessage(), $exception);
            }
        }
    }

    /**
     * Broadcast an event.
     *
     * @TODO: Add queue support here if the Event has a queueable trait {JO: 2021-03-19}
     *
     * @param EventContract $event
     * @return void
     * @throws Exception
     */
    protected static function broadcastEvent(EventContract $event)
    {
        // may attempt to transform the event before passing down to standard subscribers
        EventTransformers::transform($event);

        foreach (static::getSubscribers($event) as $subscriberClass) {
            try {
                static::getSubscriber($subscriberClass)->handle($event);
            } catch (Exception $exception) {
                EventBroadcastFailedException::getNewInstance($exception->getMessage(), $exception);
            }
        }
    }

    /**
     * Gets a subscriber for the given class.
     *
     * @param string $subscriberClass
     * @return SubscriberContract
     * @throws InvalidArgumentException
     */
    protected static function getSubscriber(string $subscriberClass) : SubscriberContract
    {
        try {
            $subscriber = ContainerFactory::getInstance()->getSharedContainer()->get($subscriberClass);
        } catch (ContainerException $exception) {
            // Try new without params - this was the behavior before DI container.
            $subscriber = new $subscriberClass();
        }

        if (! (is_object($subscriber) && is_a($subscriber, SubscriberContract::class))) {
            throw new InvalidArgumentException("{$subscriberClass} does not implement SubscriberContract");
        }

        return $subscriber;
    }

    /**
     * Gets a list of subscribers.
     *
     * Returns for a given event if provided or all events if none is provided.
     *
     * @param EventContract $event
     * @return string[] array of class names
     * @throws Exception
     */
    public static function getSubscribers(EventContract $event) : array
    {
        return ConfigHelper::getClassNamesUsingClassOrInterfacesAsKeys('events.listeners', $event);
    }

    /**
     * Check if a given event has a given subscriber.
     *
     * @param EventContract $event
     * @param SubscriberContract $subscriber
     * @return bool
     * @throws Exception
     */
    public static function hasSubscriber(EventContract $event, SubscriberContract $subscriber) : bool
    {
        return ArrayHelper::contains(static::getSubscribers($event), get_class($subscriber));
    }
}
