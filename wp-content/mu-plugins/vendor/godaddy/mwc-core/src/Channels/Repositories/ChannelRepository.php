<?php

namespace GoDaddy\WordPress\MWC\Core\Channels\Repositories;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Channels\Cache\Types\ChannelCache;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Http\Requests\ChannelRequest;

class ChannelRepository
{
    use CanGetNewInstanceTrait;

    /**
     * Gets channel ID from cache or channel request.
     *
     * Note: ChannelsRepository uses the MWC extensions API which in turn requests the ChannelID from the Pagely management API,
     *
     * @param string $channelId
     * @return array<string, string|bool>|null
     */
    public function getChannelDataWithCache(string $channelId) : ?array
    {
        $cached = ChannelCache::getNewInstance($channelId)->remember(static function () use ($channelId) {
            try {
                $response = ChannelRequest::withAuth()
                    ->setPath("/channels/{$channelId}")
                    ->send();

                if ($response->isSuccess()) {
                    return $response->getBody() ?: null;
                }
            } catch (Exception $e) {
            }

            return null;
        });

        /** @var array<string, string|bool> $cached */
        $cached = TypeHelper::array($cached, []);

        return empty($cached) ? null : $cached;
    }
}
