<?php

namespace GoDaddy\WordPress\MWC\Core\Channels\Interceptors;

use DateInterval;
use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Exceptions\AdapterException;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Schedule\Schedule;
use GoDaddy\WordPress\MWC\Core\Channels\Cache\Types\ChannelCache;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Http\Requests\ChannelRequest;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\OrderAdapter;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Repositories\OrdersRepository;
use WC_Order;

/**
 * Retrieves the order originating channel from the Channels API and updates the order information with its ID.
 */
class FindOrCreateOrderChannelActionInterceptor extends AbstractInterceptor
{
    /** @var string the action used to find or create the order channel and update the order */
    public const FIND_OR_CREATE_ORDER_CHANNEL_ACTION = 'mwc_find_or_create_order_channel';

    /**
     * Adds hooks.
     *
     * @return void
     * @throws Exception
     */
    public function addHooks()
    {
        Register::action()
                ->setGroup(static::FIND_OR_CREATE_ORDER_CHANNEL_ACTION)
                ->setHandler([$this, 'findOrCreateChannel'])
                ->setArgumentsCount(3)
                ->execute();
    }

    /**
     * Finds or creates the order channel in the Channels API.
     *
     * @param int|mixed $orderId
     * @param array<string, mixed>|mixed $requestBody
     * @param int|mixed $attemptNumber
     * @return void
     */
    public function findOrCreateChannel($orderId, $requestBody, $attemptNumber) : void
    {
        try {
            if (! $wcOrder = OrdersRepository::get($orderId)) {
                throw new Exception("Failed to fetch WC_Order for ID {$orderId}");
            }

            $order = OrderAdapter::getNewInstance($wcOrder)->convertFromSource();
            $response = ChannelRequest::getNewInstance()
                                      ->setMethod('POST')
                                      ->setPath('/channels/find-or-create')
                                      ->setBody(TypeHelper::array($requestBody, []))
                                      ->send();

            if ($response->isSuccess()) {
                $responseBody = $response->getBody() ?? [];

                $this->saveOrderChannelId($order, ArrayHelper::get($responseBody, 'id'));
                $this->updateChannelCache($responseBody);
            } else {
                $this->handleError($orderId, $requestBody, $attemptNumber, null);
            }
        } catch (Exception $exception) {
            $this->handleError($orderId, $requestBody, $attemptNumber, $exception);
        }
    }

    /**
     * Handles an exception or error response from the API.
     *
     * May schedule a new request, depending on the response and number of attempts.
     *
     * @param int|mixed $orderId
     * @param array<string, mixed>|mixed $requestBody
     * @param int|mixed $attemptNumber
     * @param Exception|null $exception
     * @return void
     */
    protected function handleError($orderId, $requestBody, $attemptNumber, ?Exception $exception = null)
    {
        $maxAttempts = Configuration::get('channels.api.maxAttempts', 3);

        if ($attemptNumber >= $maxAttempts) {
            new SentryException("Maximum attempts exceeded trying to find or create a channel for order {$orderId}", $exception);

            return;
        }

        $delay = Configuration::get('channels.api.retryDelay', 30);

        try {
            Schedule::singleAction()
                    ->setName(static::FIND_OR_CREATE_ORDER_CHANNEL_ACTION)
                    ->setScheduleAt((new DateTime())->add(new DateInterval("PT{$delay}S")))
                    ->setArguments($orderId, $requestBody, $attemptNumber + 1)
                    ->schedule();
        } catch (Exception $exception) {
            new SentryException("Error scheduling an action to find or create a channel for order {$orderId}", $exception);
        }
    }

    /**
     * Saves the order originating channel ID.
     *
     * @param Order $order
     * @param string $channelId
     * @return void
     * @throws AdapterException
     */
    protected function saveOrderChannelId(Order $order, string $channelId) : void
    {
        $order->setOriginatingChannelId($channelId);

        $wcOrder = OrderAdapter::getNewInstance(new WC_Order())->convertToSource($order);
        $wcOrder->save();
    }

    /**
     * Updates the channel cache for this channel.
     *
     * @param array<string, mixed> $channelData Channel data from the successful API response.
     * @return void
     */
    protected function updateChannelCache(array $channelData) : void
    {
        $channelId = ArrayHelper::get($channelData, 'id');

        if ($channelId) {
            ChannelCache::getNewInstance($channelId)->set($channelData);
        } else {
            new SentryException('Missing channel ID from API response body.');
        }
    }
}
