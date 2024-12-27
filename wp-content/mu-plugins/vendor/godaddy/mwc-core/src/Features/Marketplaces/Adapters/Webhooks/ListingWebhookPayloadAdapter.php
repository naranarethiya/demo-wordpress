<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Adapters\Webhooks;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Channel;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Listing;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Webhooks\ListingWebhookPayload;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Repositories\ChannelRepository;

/**
 * Adapts data from a GDM listing webhook payload to a native {@see ListingWebhookPayload} object.
 */
class ListingWebhookPayloadAdapter implements DataSourceAdapterContract
{
    use CanGetNewInstanceTrait;

    /** @var array<string, mixed> Listing data from the webhook payload */
    protected array $source;

    /**
     * Constructor.
     *
     * @param array<string, mixed> $decodedWebhookPayload Decoded data from the webhook payload.
     */
    public function __construct(array $decodedWebhookPayload)
    {
        $this->source = $decodedWebhookPayload;
    }

    /**
     * Converts the decoded payload into {@see ListingWebhookPayload} objects.
     *
     * @return ListingWebhookPayload
     */
    public function convertFromSource() : ListingWebhookPayload
    {
        $productId = ArrayHelper::get($this->source, 'payload.details.gdwoo_id') ?: ArrayHelper::get($this->source, 'payload.gdwoo_id');

        return (new ListingWebhookPayload())
            ->setEventType(TypeHelper::string(ArrayHelper::get($this->source, 'event_type'), ''))
            ->setIsExpectedEvent($this->isListingEvent())
            ->setProductId(! empty($productId) ? TypeHelper::int($productId, 0) : null)
            ->setListing($this->adaptListing());
    }

    /**
     * Determines if the webhook received is for a listing event.
     *
     * @return bool
     */
    protected function isListingEvent() : bool
    {
        return in_array(ArrayHelper::get($this->source, 'event_type'), ['webhook_listing_created', 'webhook_listing_updated', 'webhook_listing_deleted'], true);
    }

    /**
     * Creates a Listing object from the webhook payload.
     *
     * @return Listing|null
     */
    protected function adaptListing() : ?Listing
    {
        // ensures we have at least one piece of required information
        if (! $listingId = ArrayHelper::get($this->source, 'payload.id')) {
            return null;
        }

        return Listing::getNewInstance()
            ->setListingId(TypeHelper::string(is_numeric($listingId) ? (string) $listingId : $listingId, ''))
            ->setChannelType($this->adaptChannelType())
            ->setIsPublished('active' === strtolower(TypeHelper::string(ArrayHelper::get($this->source, 'payload.status', ''), '')))
            ->setLink(TypeHelper::string(ArrayHelper::get($this->source, 'payload.details.path', ''), ''));
    }

    /**
     * Adapts the channel type from the payload. It's given to us in display format (e.g. "Amazon") and we need to convert it to slug format (e.g. "amazon").
     *
     * @return string
     */
    protected function adaptChannelType() : string
    {
        $channelDisplayName = TypeHelper::string(ArrayHelper::get($this->source, 'payload.details.channel_type_display_name'), '');
        $channelType = $this->normalizeChannelTypeFromName($channelDisplayName);
        $channel = '' !== $channelType ? ChannelRepository::getByType($channelType) : null;

        return $channel ? $channel->getType() : $channelType;
    }

    /**
     * Normalizes a channel type from a channel (display) name.
     *
     * This is necessary as the Marketplaces API may have inconsistencies with the channel display name values.
     *
     * @param string $channelName
     * @return string
     */
    protected function normalizeChannelTypeFromName(string $channelName) : string
    {
        $matches = [];

        preg_match_all('/\b('.implode('|', ChannelRepository::getTypes()).')\b/i', trim(strtolower($channelName)), $matches);

        return implode('', $matches[0] ?? '');
    }

    /**
     * {@inheritDoc}
     */
    public function convertToSource()
    {
        // Not implemented.
        return [];
    }
}
