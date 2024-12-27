<?php

namespace GoDaddy\WordPress\MWC\Core\Channels\Events\Transformers;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\ModelEvent;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Platforms\Exceptions\PlatformRepositoryException;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;
use GoDaddy\WordPress\MWC\Core\Channels\Repositories\ChannelRepository;
use GoDaddy\WordPress\MWC\Core\Events\Transformers\AbstractOrderEventTransformer;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Orders\Order;

/**
 * Transformer to add channel-related data to order events.
 */
class OrderEventTransformer extends AbstractOrderEventTransformer
{
    /**
     * Transforms the event data if necessary.
     *
     * @param ModelEvent $event
     * @return void
     */
    public function handle(EventContract $event) : void
    {
        /** @var Order $order */
        $order = $event->getModel();
        $data = $event->getData();

        $channelData = $this->getMarketplacesChannelData($order);

        if (! $channelData) {
            try {
                $channelId = PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->getChannelId();
            } catch (PlatformRepositoryException $e) {
                $channelId = null;
            }
            $channelData = $this->getChannelDataById($channelId);
        }

        ArrayHelper::set($data, 'resource.channel', ArrayHelper::where($channelData, static function ($_, $key) {
            return in_array($key, ['id', 'subType']);
        }));

        $event->setData($data);
    }

    /**
     * Get marketplaces channel data, if it is a marketplaces order.
     *
     * @param Order $order
     * @return array<string, ?string>|null
     */
    protected function getMarketplacesChannelData(Order $order) : ?array
    {
        if (! $order->hasMarketplacesChannel()) {
            return null;
        }

        return $this->getChannelDataById($order->getOriginatingChannelId());
    }

    /**
     * Gets channel data if it can be found, otherwise returns channel structure with nulls.
     *
     * @param string|null $channelId
     * @return array<string, mixed>
     */
    protected function getChannelDataById(?string $channelId) : array
    {
        if ($channelId && $channelData = ChannelRepository::getNewInstance()->getChannelDataWithCache($channelId)) {
            return $channelData;
        }

        return [
            'id'      => null,
            'subType' => null,
        ];
    }
}
