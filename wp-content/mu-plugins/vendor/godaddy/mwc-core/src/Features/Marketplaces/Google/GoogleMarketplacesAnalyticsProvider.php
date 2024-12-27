<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Google;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Platforms\Exceptions\PlatformRepositoryException;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Analytics\Providers\Contracts\GoogleAnalyticsProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Marketplaces;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Models\Channel;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Repositories\ChannelRepository;

/**
 * Google Analytics provider for the Marketplaces feature.
 */
class GoogleMarketplacesAnalyticsProvider implements GoogleAnalyticsProviderContract
{
    use CanGetNewInstanceTrait;

    /** @var string */
    protected string $trackingIdOptionKey = 'mwc_marketplaces_google_shopping_tracking_id';

    /** @var string */
    protected string $conversionLabelOptionKey = 'mwc_marketplaces_google_shopping_conversion_label';

    /**
     * Determines whether the provider is active.
     *
     * @throws PlatformRepositoryException
     */
    public function isActive() : bool
    {
        return Marketplaces::shouldLoad() && ChannelRepository::isConnected(Channel::TYPE_GOOGLE);
    }

    /**
     * Gets the tracking ID.
     *
     * @return string|null
     */
    public function getTrackingId() : ?string
    {
        $trackingId = get_option($this->trackingIdOptionKey);

        return is_string($trackingId) ? $trackingId : null;
    }

    /**
     * Updates the tracking ID value.
     *
     * @param string $value
     * @return $this
     */
    public function updateTrackingId(string $value) : GoogleMarketplacesAnalyticsProvider
    {
        update_option($this->trackingIdOptionKey, $value);

        return $this;
    }

    /**
     * Gets the conversion label.
     *
     * @return string|null
     */
    public function getConversionLabel() : ?string
    {
        $conversionLabel = get_option($this->conversionLabelOptionKey);

        return is_string($conversionLabel) ? $conversionLabel : null;
    }

    /**
     * Updates the conversion label value.
     *
     * @param string $value
     * @return $this
     */
    public function updateConversionLabel(string $value) : GoogleMarketplacesAnalyticsProvider
    {
        update_option($this->conversionLabelOptionKey, $value);

        return $this;
    }

    /**
     * Gets the developer ID.
     *
     * @return string|null
     */
    public function getDeveloperId() : ?string
    {
        $developerId = Configuration::get('analytics.google.developerId');

        return is_string($developerId) ? $developerId : null;
    }
}
