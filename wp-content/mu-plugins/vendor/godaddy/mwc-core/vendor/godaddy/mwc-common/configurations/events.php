<?php

return [
    /*
     *--------------------------------------------------------------------------
     * Event Listeners / Subscribers
     *--------------------------------------------------------------------------
     *
     * The following array contains events and a list of their subscribers.  In order
     * to have a cached subscriber for a given event at optimal performance, the
     * subscriber should be listed under the events key below.
     *
     * Event with Namespace => subscriber class
     *
     * All subscribers will receive the full event object by default.  Determination
     * of if the event is queued before triggering the listener should/is done
     * via declaration on the Event itself.
     *
     */
    'listeners' => [
        // 'GoDaddy\WordPress\MWC\Common\Events\MyEvent' => [Subscriber::class],
        // MyEvent::class => ['GoDaddy\WordPress\MWC\Common\Subscribers\MySubscriber'],
    ],
    /*
     *--------------------------------------------------------------------------
     * Event Transformers
     *--------------------------------------------------------------------------
     *
     * The following array contains events and a list of their transformers.
     *
     * Event with Namespace => transformer class
     *
     * All transformers will receive the full event object by default.  Determination
     * of if the event is queued before triggering the listener should/is done
     * via declaration on the Event itself.
     *
     */
    'transformers' => [
        // GoDaddy\WordPress\MWC\Common\Events\MyEvent::class => [Transformer::class],
    ],
];
