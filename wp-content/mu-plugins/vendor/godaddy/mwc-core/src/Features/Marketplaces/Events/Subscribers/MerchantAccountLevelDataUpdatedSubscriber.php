<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\Subscribers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\SubscriberContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\ChannelConnectedEvent;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\MerchantAccountLevelDataUpdatedEvent;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\MerchantProvisionedEvent;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Http\Requests\UpdateMerchantAccountLevelDataRequest;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Channel;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Repositories\ChannelRepository;

/**
 * Subscriber for events related to updates to merchant account level data.
 */
class MerchantAccountLevelDataUpdatedSubscriber implements SubscriberContract
{
    /**
     * Issues a request to update account-level data settings in Marketplaces when:
     * - The merchant is provisioned
     * - A new sales channel is connected to Marketplaces
     * - Tax or shipping settings are updated by the merchant in the WooCommerce store.
     *
     * @param EventContract $event
     * @return void
     */
    public function handle(EventContract $event)
    {
        if (! $this->shouldHandle($event)) {
            return;
        }

        try {
            $response = UpdateMerchantAccountLevelDataRequest::getNewInstance()->send();

            if (! $response->isSuccess()) {
                new SentryException("GoDaddy Marketplaces merchant account data update failed with status code: {$response->getStatus()}");
            }
        } catch (SentryException $exception) {
            // the error will be automatically reported to sentry
        } catch (Exception $exception) {
            new SentryException('Failed to update GoDaddy Marketplaces merchant account data', $exception);
        }
    }

    /**
     * Determines whether the event should be handled by this subscriber.
     *
     * @param EventContract $event
     * @return bool
     */
    protected function shouldHandle(EventContract $event) : bool
    {
        return (
            $event instanceof MerchantAccountLevelDataUpdatedEvent ||
            $event instanceof MerchantProvisionedEvent ||
            $event instanceof ChannelConnectedEvent
        ) && ChannelRepository::isConnected(Channel::TYPE_GOOGLE);
    }
}
