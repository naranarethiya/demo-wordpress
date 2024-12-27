<?php

namespace GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Events\Transformers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\AbstractEventTransformer;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\ModelEvent;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Models\Checkout;

/**
 * Transforms the checkout model event to include the checkout status in the object payload.
 */
class CheckoutEventTransformer extends AbstractEventTransformer
{
    /**
     * Handles checkout model events only.
     *
     * @param EventContract $event
     * @return bool
     */
    public function shouldHandle(EventContract $event) : bool
    {
        return $event instanceof ModelEvent && 'checkout' === $event->getResource();
    }

    /**
     * Adds the checkout status to the model event data.
     *
     * @param ModelEvent $event
     * @throws Exception
     */
    public function handle(EventContract $event)
    {
        /** @var Checkout $checkout */
        $checkout = $event->getModel();
        $data = $event->getData();

        ArrayHelper::set($data, 'resource.status', $checkout->getStatus());

        $event->setData($data);
    }
}
