<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Webhooks\Traits;

use GoDaddy\WordPress\MWC\Common\Http\Url;
use GoDaddy\WordPress\MWC\Common\Http\Url\Exceptions\InvalidUrlException;
use GoDaddy\WordPress\MWC\Common\Http\Url\Exceptions\InvalidUrlSchemeException;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\SiteRepository;

trait CanDetermineWebhookSubscriptionDeliveryUrl
{
    /**
     * Gets the subscription delivery URL.
     *
     * @return string
     * @throws InvalidUrlException|InvalidUrlSchemeException
     */
    protected function getSubscriptionDeliveryUrl() : string
    {
        if (defined('GD_COMMERCE_WEBHOOK_DELIVERY_BASE_URL')) {
            $url = Url::fromString(GD_COMMERCE_WEBHOOK_DELIVERY_BASE_URL);
        } else {
            $url = Url::fromString(SiteRepository::getSiteUrl())->setScheme(Url::SCHEME_HTTPS);
        }

        return $url->addQueryParameter('mwc-webhooks', 'commerce')->toString();
    }
}
